<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\BlockchainTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use kornrunner\Keccak;

class OwnerController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        $totalVehicles = Vehicle::where('owner_id', $user->id)->count();

        $totalServices = Service::whereHas('vehicle', function ($query) use ($user) {
            $query->where('owner_id', $user->id);
        })->count();

        $activeVehicles = 0;

        $vehicles = Vehicle::where('owner_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();

        $recentServices = Service::with(['vehicle', 'workshop'])
            ->whereHas('vehicle', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return view('owner.dashboard', compact(
            'totalVehicles',
            'totalServices',
            'activeVehicles',
            'vehicles',
            'recentServices'
        ));
    }

    public function vehicles(Request $request)
    {
        $search = trim($request->query('search', ''));
        $status = $request->query('status', 'all');
        if (!in_array($status, ['all', 'ACTIVE', 'INACTIVE'], true)) {
            $status = 'all';
        }
        $sort = $request->query('sort', 'newest');

        $query = Auth::user()->vehicles();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_id', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('license_plate', 'like', "%{$search}%");
            });
        }


        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at');
                break;

            case 'name-asc':
                $query->orderBy('brand')->orderBy('model');
                break;

            case 'name-desc':
                $query->orderByDesc('brand')->orderByDesc('model');
                break;

            default:
                $query->orderByDesc('created_at');
                break;
        }

        $vehicles = $query->paginate(9)->withQueryString();

        return view('owner.vehicles', compact(
            'vehicles',
            'search',
            'status',
            'sort'
        ));
    }

    public function storeVehicle(Request $request)
    {
        $vehicleData = $this->validateVehicleForm($request);

        $extraData = $request->validate([
            'vehicle_id' => [
                'required',
                'string',
                'max:100',
                'unique:vehicles,vehicle_id',
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
        ]);

        $user = Auth::user();
        $walletAddress = $extraData['wallet_address'];

        $this->assertWalletCanRegister($user, $walletAddress);

        $verification = $this->verifyVehicleRegistrationTransaction(
            $extraData['transaction_hash'],
            $extraData['vehicle_id'],
            $walletAddress
        );

        DB::transaction(function () use (
            $user,
            $vehicleData,
            $extraData,
            $walletAddress,
            $verification
        ) {
            if (!$user->wallet_address) {
                $user->wallet_address = $walletAddress;
                $user->save();
            }

            $user->vehicles()->create([
                'vehicle_id' => $extraData['vehicle_id'],
                'owner_wallet_address' => $walletAddress,

                'vehicle_type' => $vehicleData['vehicle_type'],
                'brand' => $vehicleData['brand'],
                'model' => $vehicleData['model'],
                'license_plate' => $vehicleData['license_plate'],
                'chassis_number' => $vehicleData['chassis_number'],

                'sync_status' => 'SYNCED',
            ]);

            BlockchainTransaction::create([
                'user_id' => $user->id,
                'entity_type' => 'vehicle',
                'entity_id' => $extraData['vehicle_id'],
                'transaction_hash' => $extraData['transaction_hash'],
                'contract_address' => $verification['contract_address'],
                'block_number' => $verification['block_number'],
                'status' => 'CONFIRMED',
            ]);
        });

        return redirect()
            ->route('owner.vehicles')
            ->with(
                'success',
                'Kendaraan berhasil didaftarkan ke blockchain dan disinkronkan ke MySQL.'
            );
    }

    private function validateVehicleForm(Request $request): array
    {
        if (!$request->filled('chassis_number') && $request->filled('vin')) {
            $request->merge([
                'chassis_number' => $request->input('vin'),
            ]);
        }


        return $request->validate([
            'vehicle_type' => ['required', 'string', 'max:50'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],

            'year' => [
                'required',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1),
            ],

            'license_plate' => [
                'required',
                'string',
                'max:20',
                'unique:vehicles,license_plate',
            ],

            'chassis_number' => [
                'required',
                'string',
                'max:100',
                'unique:vehicles,chassis_number',
            ],


            'color' => ['required', 'string', 'max:50'],
        ]);
    }

    public function prepareVehicleRegistration(Request $request)
    {
        $walletData = $request->validate([
            'wallet_address' => [
                'required',
                'regex:/^0x[a-fA-F0-9]{40}$/',
            ],
        ]);

        $user = Auth::user();

        $this->assertWalletCanRegister(
            $user,
            $walletData['wallet_address']
        );

        $this->validateVehicleForm($request);

        do {
            $vehicleId = 'SC-VHC-' . now()->format('Ymd') . '-'
                . Str::upper(Str::random(8));
        } while (
            Vehicle::where('vehicle_id', $vehicleId)->exists()
        );

        return response()->json([
            'vehicle_id' => $vehicleId,
        ]);
    }

    private function assertWalletCanRegister($user, string $walletAddress): void
    {
        if (
            $user->wallet_address &&
            strtolower($user->wallet_address) !== strtolower($walletAddress)
        ) {
            throw ValidationException::withMessages([
                'wallet_address' =>
                    'Wallet MetaMask berbeda dari wallet yang sudah terhubung ke akun ini.',
            ]);
        }
    }

    private function verifyVehicleRegistrationTransaction(
        string $transactionHash,
        string $vehicleId,
        string $walletAddress
    ): array {
        $rpcUrl = config('services.servicechain.rpc_url');
        $contractAddress = config('services.servicechain.contract_address');

        if (!$rpcUrl || !$contractAddress) {
            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Konfigurasi koneksi blockchain belum lengkap.',
            ]);
        }

        try {
            $transaction = $this->serviceChainRpc(
                $rpcUrl,
                'eth_getTransactionByHash',
                [$transactionHash]
            );

            $receipt = $this->serviceChainRpc(
                $rpcUrl,
                'eth_getTransactionReceipt',
                [$transactionHash]
            );
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Laravel tidak dapat memeriksa transaksi pada jaringan blockchain.',
            ]);
        }

        if (!$transaction || !$receipt) {
            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Transaksi belum ditemukan atau belum mendapatkan receipt.',
            ]);
        }

        if (strtolower($receipt['status'] ?? '') !== '0x1') {
            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Transaksi blockchain gagal.',
            ]);
        }

        if (
            strtolower($transaction['from'] ?? '') !== strtolower($walletAddress)
        ) {
            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Wallet pengirim transaksi tidak sesuai dengan wallet pemilik.',
            ]);
        }

        if (
            strtolower($transaction['to'] ?? '') !== strtolower($contractAddress)
        ) {
            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Transaksi tidak dikirim ke kontrak ServiceChain.',
            ]);
        }

        $eventTopic = '0x' . Keccak::hash(
            'VehicleRegistered(string,address)',
            256
        );

        $validEventFound = false;

        foreach ($receipt['logs'] ?? [] as $log) {
            $topics = array_map(
                'strtolower',
                $log['topics'] ?? []
            );

            if (
                strtolower($log['address'] ?? '') !== strtolower($contractAddress) ||
                !isset($topics[0]) ||
                $topics[0] !== strtolower($eventTopic)
            ) {
                continue;
            }

            $data = strtolower(
                preg_replace('/^0x/i', '', $log['data'] ?? '') ?? ''
            );

            if (strlen($data) < 192 || strlen($data) % 64 !== 0) {
                continue;
            }

            $stringOffset = hexdec(substr($data, 0, 64));

            $eventOwner = '0x' . substr($data, 88, 40);

            if (
                $stringOffset < 64 ||
                strlen($data) < ($stringOffset * 2) + 64
            ) {
                continue;
            }

            $lengthPosition = $stringOffset * 2;
            $stringLength = hexdec(
                substr($data, $lengthPosition, 64)
            );

            $stringPosition = ($stringOffset + 32) * 2;
            $vehicleHex = substr(
                $data,
                $stringPosition,
                $stringLength * 2
            );

            if (strlen($vehicleHex) !== $stringLength * 2) {
                continue;
            }

            $eventVehicleId = hex2bin($vehicleHex);

            if ($eventVehicleId === false) {
                continue;
            }

            if (
                $eventVehicleId === $vehicleId &&
                strtolower($eventOwner) === strtolower($walletAddress)
            ) {
                $validEventFound = true;
                break;
            }
        }

        if (!$validEventFound) {
            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Event VehicleRegistered tidak cocok dengan kendaraan dan wallet yang didaftarkan.',
            ]);
        }

        if (empty($receipt['blockNumber'])) {
            throw ValidationException::withMessages([
                'transaction_hash' =>
                    'Nomor blok transaksi tidak ditemukan.',
            ]);
        }

        return [
            'contract_address' => $contractAddress,
            'block_number' => (int) hexdec($receipt['blockNumber']),
        ];
    }

    private function serviceChainRpc(
        string $rpcUrl,
        string $method,
        array $params
    ): mixed {
        $response = Http::timeout(15)->post($rpcUrl, [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1,
        ]);

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

    public function history(Request $request)
    {
        $search = trim($request->query('search', ''));
        $sort = $request->query('sort', 'newest');

        $query = Service::with([
            'vehicle.owner',
            'workshop',
            'owner',
        ])->whereHas('vehicle', function ($vehicleQuery) {
            $vehicleQuery->where('owner_id', Auth::id());
        });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('service_id', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                        $vehicleQuery
                            ->where('vehicle_id', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
            });
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at');
                break;

            case 'name-asc':
                $query->join(
                    'vehicles',
                    'service_records.vehicle_id',
                    '=',
                    'vehicles.vehicle_id'
                )
                    ->orderBy('vehicles.brand')
                    ->orderBy('vehicles.model')
                    ->select('service_records.*');
                break;

            case 'name-desc':
                $query->join(
                    'vehicles',
                    'service_records.vehicle_id',
                    '=',
                    'vehicles.vehicle_id'
                )
                    ->orderByDesc('vehicles.brand')
                    ->orderByDesc('vehicles.model')
                    ->select('service_records.*');
                break;

            default:
                $query->orderByDesc('created_at');
                break;
        }

        $services = $query->paginate(10)->withQueryString();

        return view('owner.history', compact(
            'services',
            'search',
            'sort'
        ));
    }

    public function profile()
    {
        $user = Auth::user();

        return view('owner.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
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
            ->route('owner.profile')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}