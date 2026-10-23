<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\BlockchainTransaction;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use kornrunner\Keccak;
use Illuminate\Support\Facades\Storage;

class WorkshopController extends Controller
{
    private function currentWorkshop()
    {
        $workshop = Auth::user()->workshop;

        abort_unless($workshop, 403, 'Data bengkel tidak ditemukan.');

        return $workshop;
    }

    public function checkWallet(Request $request)
    {
        $user = Auth::user();
        $workshop = $this->currentWorkshop();

        abort_unless(
            strtolower((string) $user->status) === 'active',
            403,
            'Akun pengguna tidak aktif.'
        );

        abort_unless(
            $workshop->status === 'ACTIVE',
            403,
            'Bengkel belum disetujui atau tidak aktif.'
        );

        $data = $request->validate([
            'wallet_address' => [
                'required',
                'regex:/^0x[a-fA-F0-9]{40}$/',
            ],
        ]);

        $wallet = strtolower($data['wallet_address']);

        $savedWorkshopWallet = strtolower(
            (string) $workshop->wallet_address
        );

        if (
            $savedWorkshopWallet !== '' &&
            $savedWorkshopWallet !== $wallet
        ) {
            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Bengkel sudah terhubung dengan wallet yang berbeda.',
            ]);
        }

        $savedUserWallet = strtolower(
            (string) $user->wallet_address
        );

        if (
            $savedUserWallet !== '' &&
            $savedUserWallet !== $wallet
        ) {
            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Wallet berbeda dari wallet yang tersimpan pada akun ini.',
            ]);
        }

        $walletUsedByAnotherUser = User::query()
            ->whereNotNull('wallet_address')
            ->whereRaw('LOWER(wallet_address) = ?', [$wallet])
            ->where('id', '!=', $user->id)
            ->exists();

        $walletUsedByAnotherWorkshop = Workshop::query()
            ->whereNotNull('wallet_address')
            ->whereRaw('LOWER(wallet_address) = ?', [$wallet])
            ->where('id', '!=', $workshop->id)
            ->exists();

        if (
            $walletUsedByAnotherUser ||
            $walletUsedByAnotherWorkshop
        ) {
            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Wallet sudah digunakan akun lain dan tidak dapat dipakai oleh bengkel ini.',
            ]);
        }

        return response()->json([
            'available' => true,
            'wallet_address' => $data['wallet_address'],
            'message' => 'Wallet tersedia untuk proses registrasi bengkel.',
        ]);
    }

    public function connectWallet(Request $request)
    {
        $user = Auth::user();
        $workshop = $this->currentWorkshop();

        abort_unless(
            strtolower((string) $user->status) === 'active',
            403,
            'Akun pengguna tidak aktif.'
        );

        abort_unless(
            $workshop->status === 'ACTIVE',
            403,
            'Bengkel belum disetujui atau tidak aktif.'
        );

        $data = $request->validate([
            'wallet_address' => [
                'required',
                'regex:/^0x[a-fA-F0-9]{40}$/',
            ],

            'registration_transaction_hash' => [
                'required',
                'regex:/^0x[a-fA-F0-9]{64}$/',
            ],
        ]);

        $wallet = $data['wallet_address'];
        $normalizedWallet = strtolower($wallet);

        if (
            $workshop->wallet_address &&
            strtolower($workshop->wallet_address) !== $normalizedWallet
        ) {
            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Bengkel sudah memiliki wallet yang berbeda.',
            ]);
        }

        if (
            $user->wallet_address &&
            strtolower($user->wallet_address) !== $normalizedWallet
        ) {
            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Wallet berbeda dari wallet yang sudah terkait dengan akun.',
            ]);
        }

        $otherWorkshopUsesWallet = Workshop::query()
            ->whereNotNull('wallet_address')
            ->whereRaw('LOWER(wallet_address) = ?', [
                $normalizedWallet,
            ])
            ->where('id', '!=', $workshop->id)
            ->exists();

        $otherUserUsesWallet = User::query()
            ->whereNotNull('wallet_address')
            ->whereRaw('LOWER(wallet_address) = ?', [
                $normalizedWallet,
            ])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($otherWorkshopUsesWallet || $otherUserUsesWallet) {
            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Wallet sudah terhubung ke akun lain.',
            ]);
        }

        $registration = $this->verifyWorkshopRegistration(
            $data['registration_transaction_hash'],
            $wallet,
            $workshop->workshop_name
        );

        $activation = $this->activateWorkshopOnChain(
            $wallet,
            $workshop->workshop_name
        );

        if (($activation['status'] ?? null) !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Aktivasi wallet belum berhasil dikonfirmasi.',
            ]);
        }

        DB::transaction(function () use (
            $user,
            $workshop,
            $wallet,
            $registration,
            $activation,
            $data
        ) {
            $user->wallet_address = $wallet;
            $user->save();

            $workshop->wallet_address = $wallet;
            $workshop->save();

            BlockchainTransaction::firstOrCreate(
                [
                    'transaction_hash' =>
                        $data['registration_transaction_hash'],
                ],
                [
                    'user_id' => $user->id,
                    'entity_type' => 'workshop_registration',
                    'entity_id' => (string) $workshop->id,
                    'contract_address' =>
                        config('services.servicechain.contract_address'),
                    'block_number' => $registration['block_number'],
                    'status' => 'CONFIRMED',
                ]
            );

            if (!empty($activation['activation_tx_hash'])) {
                BlockchainTransaction::firstOrCreate(
                    [
                        'transaction_hash' =>
                            $activation['activation_tx_hash'],
                    ],
                    [
                        'user_id' => $user->id,
                        'entity_type' => 'workshop_activation',
                        'entity_id' => (string) $workshop->id,
                        'contract_address' =>
                            config('services.servicechain.contract_address'),
                        'block_number' => $activation['block_number'],
                        'status' => 'CONFIRMED',
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'wallet_address' => $wallet,
            'blockchain_status' => 'ACTIVE',
            'registration_transaction_hash' =>
                $data['registration_transaction_hash'],
            'activation_transaction_hash' =>
                $activation['activation_tx_hash'],
        ]);
    }

    private function verifyWorkshopRegistration(
        string $transactionHash,
        string $walletAddress,
        string $expectedName
    ): array {
        try {
            $transaction = $this->serviceChainRpc(
                'eth_getTransactionByHash',
                [$transactionHash]
            );

            $receipt = $this->serviceChainRpc(
                'eth_getTransactionReceipt',
                [$transactionHash]
            );
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'registration_transaction_hash' =>
                    'Transaksi registrasi tidak dapat diperiksa.',
            ]);
        }

        $contractAddress = config(
            'services.servicechain.contract_address'
        );

        if (!$transaction || !$receipt) {
            throw ValidationException::withMessages([
                'registration_transaction_hash' =>
                    'Transaksi registrasi belum ditemukan.',
            ]);
        }

        if (strtolower($receipt['status'] ?? '') !== '0x1') {
            throw ValidationException::withMessages([
                'registration_transaction_hash' =>
                    'Transaksi registrasi gagal.',
            ]);
        }

        if (
            strtolower($transaction['from'] ?? '') !==
            strtolower($walletAddress)
        ) {
            throw ValidationException::withMessages([
                'registration_transaction_hash' =>
                    'Wallet pengirim transaksi tidak cocok.',
            ]);
        }

        if (
            strtolower($transaction['to'] ?? '') !==
            strtolower($contractAddress)
        ) {
            throw ValidationException::withMessages([
                'registration_transaction_hash' =>
                    'Transaksi tidak ditujukan ke kontrak ServiceChain.',
            ]);
        }

        $selector = '0x' . substr(
            Keccak::hash('registerWorkshop(string)', 256),
            0,
            8
        );

        if (
            strtolower(substr($transaction['input'] ?? '', 0, 10)) !==
            strtolower($selector)
        ) {
            throw ValidationException::withMessages([
                'registration_transaction_hash' =>
                    'Transaksi bukan pemanggilan registerWorkshop().',
            ]);
        }

        $eventTopic = '0x' . Keccak::hash(
            'WorkshopRegistered(address,string)',
            256
        );

        $walletTopic = '0x' . str_pad(
            strtolower(substr($walletAddress, 2)),
            64,
            '0',
            STR_PAD_LEFT
        );

        $eventFound = false;

        foreach ($receipt['logs'] ?? [] as $log) {
            $topics = array_map(
                'strtolower',
                $log['topics'] ?? []
            );

            if (
                strtolower($log['address'] ?? '') !==
                    strtolower($contractAddress) ||
                !isset($topics[0], $topics[1]) ||
                $topics[0] !== strtolower($eventTopic) ||
                $topics[1] !== strtolower($walletTopic)
            ) {
                continue;
            }

            $hexData = preg_replace(
                '/^0x/',
                '',
                strtolower($log['data'] ?? '0x')
            );

            if (strlen($hexData) < 128) {
                continue;
            }

            $offset = (int) hexdec(substr($hexData, 0, 64));

            $length = (int) hexdec(
                substr($hexData, $offset * 2, 64)
            );

            $nameHex = substr(
                $hexData,
                $offset * 2 + 64,
                $length * 2
            );

            $eventName = $nameHex !== ''
                ? hex2bin($nameHex)
                : '';

            if ($eventName === $expectedName) {
                $eventFound = true;
                break;
            }
        }

        if (!$eventFound) {
            throw ValidationException::withMessages([
                'registration_transaction_hash' =>
                    'Event registrasi tidak cocok dengan wallet dan nama bengkel.',
            ]);
        }

        if (empty($receipt['blockNumber'])) {
            throw ValidationException::withMessages([
                'registration_transaction_hash' =>
                    'Nomor blok transaksi tidak ditemukan.',
            ]);
        }

        return [
            'block_number' => (int) hexdec(
                $receipt['blockNumber']
            ),
        ];
    }

    private function activateWorkshopOnChain(
        string $walletAddress,
        string $workshopName
    ): array {
        $privateKey = config(
            'services.servicechain.admin_private_key'
        );

        if (!$privateKey) {
            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Private key admin kontrak belum dikonfigurasi pada backend.',
            ]);
        }

        $script = base_path('scripts/approve-workshop.cjs');

        if (!is_file($script)) {
            throw new \RuntimeException(
                'Skrip aktivasi workshop tidak ditemukan.'
            );
        }

        $environment = [];

        $allEnvironment = array_merge(
            (array) getenv(),
            $_SERVER,
            $_ENV
        );

        foreach ($allEnvironment as $key => $value) {
            if (
                !is_string($key) ||
                (!is_string($value) && !is_numeric($value))
            ) {
                continue;
            }

            $environment[strtoupper($key)] = (string) $value;
        }

        $systemRoot = getenv('SystemRoot')
            ?: getenv('WINDIR')
            ?: ($_SERVER['SystemRoot'] ?? null)
            ?: ($_SERVER['WINDIR'] ?? null);

        if (!$systemRoot) {
            throw new \RuntimeException(
                'Environment SystemRoot Windows tidak ditemukan.'
            );
        }

        $environment['SYSTEMROOT'] = $systemRoot;
        $environment['WINDIR'] = $systemRoot;

        $environment['PATH'] = getenv('PATH')
            ?: ($_SERVER['PATH'] ?? $_SERVER['Path'] ?? '');

        $environment['TEMP'] = getenv('TEMP')
            ?: sys_get_temp_dir();

        $environment['TMP'] = getenv('TMP')
            ?: sys_get_temp_dir();

        $environment['SERVICECHAIN_RPC_URL'] =
            (string) config('services.servicechain.rpc_url');

        $environment['SERVICECHAIN_CONTRACT_ADDRESS'] =
            (string) config('services.servicechain.contract_address');

        $environment['SERVICECHAIN_ADMIN_PRIVATE_KEY'] =
            (string) $privateKey;

        $process = new Process(
            [
                'node',
                $script,
                $walletAddress,
                $workshopName,
            ],
            base_path(),
            $environment
        );

        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            report(new \RuntimeException(
                'Aktivasi workshop gagal: ' .
                $process->getErrorOutput()
            ));

            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Registrasi wallet mungkin sudah tercatat, tetapi aktivasi belum selesai. Jangan registrasi ulang; periksa log Laravel.',
            ]);
        }

        $result = json_decode(
            trim($process->getOutput()),
            true
        );

        if (!is_array($result)) {
            throw new \RuntimeException(
                'Respons skrip aktivasi tidak valid.'
            );
        }

        return $result;
    }

    private function serviceChainRpc(
        string $method,
        array $params
    ): mixed {
        $response = Http::timeout(15)->post(
            config('services.servicechain.rpc_url'),
            [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => 1,
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Permintaan JSON-RPC gagal.'
            );
        }

        $payload = $response->json();

        if (isset($payload['error'])) {
            throw new \RuntimeException(
                'Node blockchain mengembalikan error JSON-RPC.'
            );
        }

        return $payload['result'] ?? null;
    }

    public function records(Request $request)
    {
        $workshop = $this->currentWorkshop();

        $search = trim($request->query('search', ''));
        $sort = $request->query('sort', 'newest');

        $query = Service::with([
            'vehicle.owner',
            'owner',
            'workshop',
        ])->where('workshop_id', $workshop->id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('service_id', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                        $vehicleQuery
                            ->where('vehicle_id', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('license_plate', 'like', "%{$search}%");
                    });
            });
        }

        if ($sort === 'oldest') {
            $query->orderBy('created_at');
        } elseif ($sort === 'name') {
            $query->orderBy(
                Vehicle::select('brand')
                    ->whereColumn(
                        'vehicles.vehicle_id',
                        'service_records.vehicle_id'
                    )
            );
        } else {
            $query->orderByDesc('created_at');
        }

        $services = $query->paginate(10)->withQueryString();

        $vehicles = Vehicle::with('owner')
            ->where('sync_status', 'SYNCED')
            ->orderBy('brand')
            ->orderBy('model')
            ->get();

        return view('workshop.records', compact(
            'services',
            'vehicles',
            'search',
            'sort'
        ));
    }

    public function prepareRecord(Request $request)
    {
        $user = Auth::user();
        $workshop = $this->currentWorkshop();

        $this->assertWorkshopCanRecord($user, $workshop);

        $data = $this->validateServiceForm($request);

        $walletData = $request->validate([
            'wallet_address' => [
                'required',
                'regex:/^0x[a-fA-F0-9]{40}$/',
            ],
        ]);

        $wallet = strtolower($walletData['wallet_address']);

        if (
            $wallet !== strtolower((string) $workshop->wallet_address) ||
            $wallet !== strtolower((string) $user->wallet_address)
        ) {
            abort(403, 'Wallet MetaMask tidak sesuai dengan akun bengkel.');
        }

        $vehicle = Vehicle::where(
            'vehicle_id',
            $data['vehicle_id']
        )->firstOrFail();

        return response()->json([
            'ready' => true,
            'vehicle_id' => $vehicle->vehicle_id,
            'message' => 'Data siap dikirim ke blockchain.',
        ]);
    }

    public function storeRecord(Request $request)
    {
        $user = Auth::user();
        $workshop = $this->currentWorkshop();

        $this->assertWorkshopCanRecord($user, $workshop);

        $data = $this->validateServiceForm($request);

        $meta = $request->validate([
            'service_id' => [
                'required',
                'regex:/^[1-9][0-9]*$/',
                'unique:service_records,service_id',
            ],
            'wallet_address' => [
                'required',
                'regex:/^0x[a-fA-F0-9]{40}$/',
            ],
            'transaction_hash' => [
                'required',
                'regex:/^0x[a-fA-F0-9]{64}$/',
                'unique:blockchain_transactions,transaction_hash',
            ],
            'receipt_hash' => [
                'required',
                'regex:/^0x[a-fA-F0-9]{64}$/',
            ],
        ]);

        $wallet = strtolower($meta['wallet_address']);

        if (
            $wallet !== strtolower((string) $user->wallet_address) ||
            $wallet !== strtolower((string) $workshop->wallet_address)
        ) {
            abort(403, 'Wallet MetaMask tidak sesuai dengan akun bengkel.');
        }

        $vehicle = Vehicle::where(
            'vehicle_id',
            $data['vehicle_id']
        )->firstOrFail();

        $fileBytes = file_get_contents(
            $data['receipt']->getRealPath()
        );

        if ($fileBytes === false) {
            throw ValidationException::withMessages([
                'receipt' => 'File nota tidak dapat dibaca.',
            ]);
        }

        $computedReceiptHash = '0x' . Keccak::hash(
            $fileBytes,
            256
        );

        if (
            !hash_equals(
                strtolower($computedReceiptHash),
                strtolower($meta['receipt_hash'])
            )
        ) {
            throw ValidationException::withMessages([
                'receipt' =>
                    'Hash nota tidak cocok. Pilih kembali file nota yang digunakan pada transaksi.',
            ]);
        }

        $onChain = $this->verifyServiceRecordTransaction(
            $meta['transaction_hash']
        );

        if (
            strtolower($onChain['from']) !== $wallet ||
            strtolower($onChain['contractAddress']) !==
                strtolower(config('services.servicechain.contract_address'))
        ) {
            abort(422, 'Wallet atau kontrak transaksi tidak cocok.');
        }

        $expectedTimestamp = strtotime(
            $data['service_date'] . ' 00:00:00 UTC'
        );

        $expectedArgs = [
            'vehicleId' => $data['vehicle_id'],
            'serviceDate' => (string) $expectedTimestamp,
            'mileage' => (string) $data['mileage'],
            'cost' => (string) $data['cost'],
            'serviceType' => $data['service_type'],
            'description' => $data['description'],
            'receiptHash' => $computedReceiptHash,
        ];

        foreach ($expectedArgs as $key => $value) {
            $actual = $onChain['args'][$key] ?? null;

            if (
                !is_string($actual) ||
                (
                    in_array(
                        $key,
                        ['serviceDate', 'mileage', 'cost'],
                        true
                    )
                        ? $actual !== $value
                        : (
                            in_array(
                                $key,
                                ['receiptHash'],
                                true
                            )
                                ? strtolower($actual) !== strtolower($value)
                                : $actual !== $value
                        )
                )
            ) {
                throw ValidationException::withMessages([
                    'transaction_hash' =>
                        'Data form tidak cocok dengan data pada transaksi blockchain: ' . $key,
                ]);
            }
        }

        if (
            $onChain['serviceId'] !== $meta['service_id'] ||
            $onChain['record']['serviceId'] !== $meta['service_id']
        ) {
            throw ValidationException::withMessages([
                'service_id' =>
                    'Service ID tidak cocok dengan ID yang tercatat di blockchain.',
            ]);
        }

        if (
            strtolower($onChain['record']['serviceHash']) ===
            strtolower('0x' . str_repeat('0', 64))
        ) {
            throw ValidationException::withMessages([
                'transaction_hash' => 'Service hash tidak valid.',
            ]);
        }

        $receiptPath = $data['receipt']->store(
            'service-receipts',
            'public'
        );

        try {
            DB::transaction(function () use (
                $user,
                $workshop,
                $vehicle,
                $data,
                $meta,
                $onChain,
                $receiptPath
            ) {
                Service::create([
                    'service_id' => $meta['service_id'],
                    'vehicle_id' => $vehicle->vehicle_id,
                    'owner_id' => $vehicle->owner_id,
                    'workshop_id' => $workshop->id,
                    'receipt_path' => $receiptPath,
                    'receipt_cid' => null,
                    'service_hash' => $onChain['record']['serviceHash'],
                    'transaction_hash' => $meta['transaction_hash'],
                    'sync_status' => 'SYNCED',
                ]);

                BlockchainTransaction::create([
                    'user_id' => $user->id,
                    'entity_type' => 'service',
                    'entity_id' => $meta['service_id'],
                    'transaction_hash' => $meta['transaction_hash'],
                    'contract_address' => $onChain['contractAddress'],
                    'block_number' => $onChain['blockNumber'],
                    'status' => 'CONFIRMED',
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($receiptPath);
            throw $e;
        }

        return redirect()
            ->route('workshop.records.index')
            ->with(
                'success',
                'Catatan servis berhasil dicatat di blockchain dan disinkronkan ke MySQL.'
            );
    }

    private function assertWorkshopCanRecord($user, $workshop): void
    {
        abort_unless(
            strtolower((string) $user->status) === 'active',
            403,
            'Akun pengguna tidak aktif.'
        );

        abort_unless(
            strtoupper((string) $workshop->status) === 'ACTIVE',
            403,
            'Bengkel belum disetujui atau tidak aktif.'
        );

        $userWallet = strtolower(
            trim((string) $user->wallet_address)
        );

        $workshopWallet = strtolower(
            trim((string) $workshop->wallet_address)
        );

        abort_if(
            $userWallet === '' || $workshopWallet === '',
            403,
            'Hubungkan wallet bengkel terlebih dahulu.'
        );

        abort_unless(
            hash_equals($userWallet, $workshopWallet),
            403,
            'Wallet akun tidak sesuai dengan wallet bengkel.'
        );
    }

    private function validateServiceForm(Request $request): array
    {
        return $request->validate([
            'vehicle_id' => [
                'required',
                'string',
                'max:100',
                'exists:vehicles,vehicle_id',
            ],
            'service_type' => [
                'required',
                'string',
                'max:100',
            ],
            'service_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'mileage' => [
                'required',
                'integer',
                'min:0',
            ],
            'cost' => [
                'required',
                'integer',
                'min:0',
            ],
            'description' => [
                'required',
                'string',
                'max:10000',
            ],
            'receipt' => [
                'required',
                'image',
                'max:10240',
            ],
        ]);
    }

    private function verifyServiceRecordTransaction(
        string $transactionHash
    ): array {
        $script = base_path('scripts/verify-service-record.cjs');

        if (!is_file($script)) {
            throw new \RuntimeException(
                'Skrip verifikasi transaksi servis tidak ditemukan.'
            );
        }

        $environment = [];

        foreach (array_merge((array) getenv(), $_SERVER, $_ENV) as $key => $value) {
            if (
                is_string($key) &&
                (is_string($value) || is_numeric($value))
            ) {
                $environment[strtoupper($key)] = (string) $value;
            }
        }

        $systemRoot = getenv('SystemRoot')
            ?: getenv('WINDIR')
            ?: ($_SERVER['SystemRoot'] ?? null)
            ?: ($_SERVER['WINDIR'] ?? null);

        if (!$systemRoot) {
            throw new \RuntimeException(
                'Environment SystemRoot Windows tidak ditemukan.'
            );
        }

        $environment['SYSTEMROOT'] = $systemRoot;
        $environment['WINDIR'] = $systemRoot;
        $environment['PATH'] = getenv('PATH')
            ?: ($_SERVER['PATH'] ?? $_SERVER['Path'] ?? '');
        $environment['TEMP'] = getenv('TEMP') ?: sys_get_temp_dir();
        $environment['TMP'] = getenv('TMP') ?: sys_get_temp_dir();

        $environment['SERVICECHAIN_RPC_URL'] = (string) config(
            'services.servicechain.rpc_url'
        );

        $environment['SERVICECHAIN_CONTRACT_ADDRESS'] = (string) config(
            'services.servicechain.contract_address'
        );

        $process = new Process(
            [
                'node',
                $script,
                $transactionHash,
            ],
            base_path(),
            $environment
        );

        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            report(new \RuntimeException(
                'Verifikasi servis gagal: ' . $process->getErrorOutput()
            ));

            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Transaksi servis tidak dapat diverifikasi pada blockchain. Periksa log Laravel.',
            ]);
        }

        $result = json_decode(
            trim($process->getOutput()),
            true
        );

        if (
            !is_array($result) ||
            empty($result['success']) ||
            empty($result['record'])
        ) {
            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Hasil verifikasi transaksi tidak valid.',
            ]);
        }

        return $result;
    }

    public function showRecord(Service $service)
    {
        $workshop = $this->currentWorkshop();

        abort_unless(
            (int) $service->workshop_id === (int) $workshop->id,
            404
        );

        $service->load([
            'vehicle.owner',
            'owner',
        ]);

        return view('workshop.record-detail', compact('service'));
    }

    public function profile()
    {
        $workshop = $this->currentWorkshop();

        return view('workshop.profile', compact('workshop'));
    }

    public function updateProfile(Request $request)
    {
        $workshop = $this->currentWorkshop();
        $user = Auth::user();

        if (
            $request->filled('workshop_name') &&
            $request->input('workshop_name') !== $workshop->workshop_name
        ) {
            return back()
                ->withErrors([
                    'workshop_name' => 'Nama bengkel tidak dapat diubah melalui profil ini.',
                ])
                ->withInput();
        }

        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        if ($data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return redirect()
            ->route('workshop.profile')
            ->with('success', 'Profil akun berhasil diperbarui.');
    }

    public function dashboard()
    {
        $workshop = $this->currentWorkshop();
        $servicesQuery = Service::where('workshop_id', $workshop->id);

        $totalServices = (clone $servicesQuery)->count();

        $servicesThisMonth = (clone $servicesQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $vehiclesServed = (clone $servicesQuery)
            ->distinct()
            ->count('vehicle_id');

        $vehiclesThisMonth = (clone $servicesQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->distinct()
            ->count('vehicle_id');

        $latestService = (clone $servicesQuery)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $recentServices = Service::with(['vehicle', 'workshop'])
            ->where('workshop_id', $workshop->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('workshop.dashboard', compact(
            'workshop',
            'totalServices',
            'servicesThisMonth',
            'vehiclesServed',
            'vehiclesThisMonth',
            'latestService',
            'recentServices'
        ));
    }
}