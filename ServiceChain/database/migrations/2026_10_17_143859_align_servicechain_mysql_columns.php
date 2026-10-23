<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertVehicleBackfillWillNotCreateDuplicateChassisNumbers();
        $this->assertNumericServiceRecordsHaveBlockchainMetadata();

        $this->archiveLegacyServiceRecords();
        $this->assertServiceRecordsHaveBlockchainMetadata();

        if (Schema::hasTable('vehicles')) {
            if (Schema::hasColumn('vehicles', 'vin') && Schema::hasColumn('vehicles', 'chassis_number')) {
                DB::table('vehicles')
                    ->where(function ($query) {
                        $query->whereNull('chassis_number')
                            ->orWhere('chassis_number', '');
                    })
                    ->whereNotNull('vin')
                    ->where('vin', '!=', '')
                    ->update(['chassis_number' => DB::raw('vin')]);
            }

            if (Schema::hasColumn('vehicles', 'owner_address') && Schema::hasColumn('vehicles', 'owner_wallet_address')) {
                DB::table('vehicles')
                    ->where(function ($query) {
                        $query->whereNull('owner_wallet_address')
                            ->orWhere('owner_wallet_address', '');
                    })
                    ->whereNotNull('owner_address')
                    ->where('owner_address', '!=', '')
                    ->update(['owner_wallet_address' => DB::raw('owner_address')]);
            }

            if (Schema::hasColumn('vehicles', 'vin')) {
                Schema::table('vehicles', function (Blueprint $table) {
                    $table->dropUnique('vehicles_vin_unique');
                });
            }

            $vehicleColumnsToDrop = [];
            foreach (['owner_address', 'year', 'vin', 'color', 'registered_at', 'status'] as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $vehicleColumnsToDrop[] = $column;
                }
            }

            if ($vehicleColumnsToDrop) {
                Schema::table('vehicles', function (Blueprint $table) use ($vehicleColumnsToDrop) {
                    $table->dropColumn($vehicleColumnsToDrop);
                });
            }
        }

        if (Schema::hasTable('service_records')) {
            $serviceColumnsToDrop = [];
            foreach (['workshop_name', 'workshop_address', 'service_type', 'description', 'service_date', 'cost', 'mileage'] as $column) {
                if (Schema::hasColumn('service_records', $column)) {
                    $serviceColumnsToDrop[] = $column;
                }
            }

            if ($serviceColumnsToDrop) {
                Schema::table('service_records', function (Blueprint $table) use ($serviceColumnsToDrop) {
                    $table->dropColumn($serviceColumnsToDrop);
                });
            }
        }
    }

    /**
     * Jangan hapus salinan detail MySQL apabila catatan lama belum memiliki
     * identitas Service ID numerik dan metadata transaksi/hash hasil sinkronisasi.
     * Pemeriksaan ini bukan pembuktian RPC langsung; aplikasi melakukan verifikasi
     * transaksi sebelum menulis status SYNCED.
     */
    private function assertServiceRecordsHaveBlockchainMetadata(): void
    {
        if (!Schema::hasTable('service_records')) {
            return;
        }

        foreach (['service_id', 'sync_status', 'service_hash', 'transaction_hash'] as $column) {
            if (!Schema::hasColumn('service_records', $column)) {
                throw new \RuntimeException(
                    'Migrasi dibatalkan: kolom service_records.' . $column
                    . ' tidak ditemukan; skema berbeda dari yang diharapkan.'
                );
            }
        }

        $badIds = [];
        DB::table('service_records')
            ->select('service_id', 'sync_status', 'service_hash', 'transaction_hash')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$badIds) {
                foreach ($rows as $row) {
                    $serviceId = (string) ($row->service_id ?? '');
                    $validId = $this->isPositiveNumericServiceId($serviceId);
                    $validStatus = strtoupper((string) ($row->sync_status ?? '')) === 'SYNCED';
                    $validServiceHash = preg_match('/^0x[a-f0-9]{64}$/i', (string) ($row->service_hash ?? '')) === 1;
                    $validTransactionHash = preg_match('/^0x[a-f0-9]{64}$/i', (string) ($row->transaction_hash ?? '')) === 1;

                    if (!$validId || !$validStatus || !$validServiceHash || !$validTransactionHash) {
                        $badIds[] = $serviceId !== '' ? $serviceId : '(ID kosong)';
                        if (count($badIds) >= 10) {
                            return false;
                        }
                    }
                }
            });

        if ($badIds) {
            throw new \RuntimeException(
                'Migrasi dibatalkan untuk melindungi data: ditemukan catatan service_records yang belum memenuhi syarat SYNCED/Service ID numerik/hash/transaction_hash. Contoh Service ID: '
                . implode(', ', $badIds)
                . '. Jangan hapus detail lama secara manual; periksa dan rekonsiliasi catatan tersebut terlebih dahulu.'
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicles')) {
            $missingVehicleColumns = [];
            if (!Schema::hasColumn('vehicles', 'owner_address')) $missingVehicleColumns[] = 'owner_address';
            if (!Schema::hasColumn('vehicles', 'year')) $missingVehicleColumns[] = 'year';
            if (!Schema::hasColumn('vehicles', 'vin')) $missingVehicleColumns[] = 'vin';
            if (!Schema::hasColumn('vehicles', 'color')) $missingVehicleColumns[] = 'color';
            if (!Schema::hasColumn('vehicles', 'registered_at')) $missingVehicleColumns[] = 'registered_at';
            if (!Schema::hasColumn('vehicles', 'status')) $missingVehicleColumns[] = 'status';

            if ($missingVehicleColumns) {
                Schema::table('vehicles', function (Blueprint $table) use ($missingVehicleColumns) {
                    foreach ($missingVehicleColumns as $column) {
                        switch ($column) {
                            case 'owner_address':
                                $table->string('owner_address')->nullable();
                                break;
                            case 'year':
                                $table->unsignedSmallInteger('year')->nullable();
                                break;
                            case 'vin':
                                $table->string('vin', 100)->nullable();
                                break;
                            case 'color':
                                $table->string('color', 50)->nullable();
                                break;
                            case 'registered_at':
                                $table->timestamp('registered_at')->nullable();
                                break;
                            case 'status':
                                $table->enum('status', ['ACTIVE', 'INACTIVE'])->nullable()->default('ACTIVE');
                                break;
                        }
                    }
                });
            }

            if (Schema::hasColumn('vehicles', 'owner_address') && Schema::hasColumn('vehicles', 'owner_wallet_address')) {
                DB::table('vehicles')->whereNull('owner_address')->update(['owner_address' => DB::raw('owner_wallet_address')]);
            }
            if (Schema::hasColumn('vehicles', 'vin') && Schema::hasColumn('vehicles', 'chassis_number')) {
                DB::table('vehicles')->whereNull('vin')->update(['vin' => DB::raw('chassis_number')]);
            }
            if (Schema::hasColumn('vehicles', 'registered_at') && Schema::hasColumn('vehicles', 'created_at')) {
                DB::table('vehicles')->whereNull('registered_at')->update(['registered_at' => DB::raw('created_at')]);
            }

            if (Schema::hasColumn('vehicles', 'vin')) {
                Schema::table('vehicles', function (Blueprint $table) {
                    $table->unique('vin', 'vehicles_vin_unique');
                });
            }
        }

        if (Schema::hasTable('service_records')) {
            $missingServiceColumns = [];
            if (!Schema::hasColumn('service_records', 'workshop_name')) $missingServiceColumns[] = 'workshop_name';
            if (!Schema::hasColumn('service_records', 'workshop_address')) $missingServiceColumns[] = 'workshop_address';
            if (!Schema::hasColumn('service_records', 'service_type')) $missingServiceColumns[] = 'service_type';
            if (!Schema::hasColumn('service_records', 'description')) $missingServiceColumns[] = 'description';
            if (!Schema::hasColumn('service_records', 'service_date')) $missingServiceColumns[] = 'service_date';
            if (!Schema::hasColumn('service_records', 'cost')) $missingServiceColumns[] = 'cost';
            if (!Schema::hasColumn('service_records', 'mileage')) $missingServiceColumns[] = 'mileage';

            if ($missingServiceColumns) {
                Schema::table('service_records', function (Blueprint $table) use ($missingServiceColumns) {
                    foreach ($missingServiceColumns as $column) {
                        switch ($column) {
                            case 'workshop_name':
                                $table->string('workshop_name')->nullable();
                                break;
                            case 'workshop_address':
                                $table->text('workshop_address')->nullable();
                                break;
                            case 'service_type':
                                $table->string('service_type', 100)->nullable();
                                break;
                            case 'description':
                                $table->text('description')->nullable();
                                break;
                            case 'service_date':
                                $table->date('service_date')->nullable();
                                break;
                            case 'cost':
                                $table->decimal('cost', 15, 2)->nullable();
                                break;
                            case 'mileage':
                                $table->unsignedInteger('mileage')->nullable();
                                break;
                        }
                    }
                });
            }
        }
    }


    /**
     * Catatan dengan Service ID numerik diperlakukan sebagai calon catatan kontrak.
     * Jika metadata transaksinya tidak lengkap, migrasi berhenti sebelum perubahan apa pun.
     */
    private function assertNumericServiceRecordsHaveBlockchainMetadata(): void
    {
        if (!Schema::hasTable('service_records')) {
            return;
        }

        foreach (['id', 'service_id', 'sync_status', 'service_hash', 'transaction_hash'] as $column) {
            if (!Schema::hasColumn('service_records', $column)) {
                throw new \RuntimeException(
                    'Migrasi dibatalkan: kolom service_records.' . $column
                    . ' tidak ditemukan; skema berbeda dari yang diharapkan.'
                );
            }
        }

        $badIds = [];
        DB::table('service_records')
            ->select('id', 'service_id', 'sync_status', 'service_hash', 'transaction_hash')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$badIds) {
                foreach ($rows as $row) {
                    $serviceId = (string) ($row->service_id ?? '');
                    if (!$this->isPositiveNumericServiceId($serviceId)) {
                        continue;
                    }

                    $validStatus = strtoupper((string) ($row->sync_status ?? '')) === 'SYNCED';
                    $validServiceHash = preg_match('/^0x[a-f0-9]{64}$/i', (string) ($row->service_hash ?? '')) === 1;
                    $validTransactionHash = preg_match('/^0x[a-f0-9]{64}$/i', (string) ($row->transaction_hash ?? '')) === 1;

                    if (!$validStatus || !$validServiceHash || !$validTransactionHash) {
                        $badIds[] = $serviceId;
                        if (count($badIds) >= 10) {
                            return false;
                        }
                    }
                }
            });

        if ($badIds) {
            throw new \RuntimeException(
                'Migrasi dibatalkan sebelum perubahan data: Service ID numerik berikut belum memenuhi syarat SYNCED/hash/transaction_hash: '
                . implode(', ', $badIds)
                . '. Verifikasi catatan tersebut terlebih dahulu; migrasi tidak mengarsipkan atau menghapusnya.'
            );
        }
    }

    private function isPositiveNumericServiceId(string $serviceId): bool
    {
        return $serviceId !== ''
            && ctype_digit($serviceId)
            && ltrim($serviceId, '0') !== '';
    }

    /**
     * Pindahkan baris legacy nonnumeric ke tabel arsip dengan struktur yang sama,
     * lalu hapus hanya dari tabel operasional service_records setelah arsip terverifikasi.
     * Isi/detail nota, hash, transaction hash, foreign IDs, dan timestamps tetap tersimpan.
     */
    private function archiveLegacyServiceRecords(): void
    {
        if (!Schema::hasTable('service_records')) {
            return;
        }

        foreach (['id', 'service_id'] as $column) {
            if (!Schema::hasColumn('service_records', $column)) {
                throw new \RuntimeException(
                    "Tidak dapat mengarsipkan catatan legacy: kolom service_records.{$column} tidak ditemukan."
                );
            }
        }

        if (!Schema::hasTable('service_records_legacy')) {
            DB::statement('CREATE TABLE `service_records_legacy` LIKE `service_records`');
        }

        $sourceColumns = Schema::getColumnListing('service_records');
        $archiveColumns = Schema::getColumnListing('service_records_legacy');
        $sharedColumns = array_values(array_intersect($sourceColumns, $archiveColumns));
        $missingArchiveColumns = array_values(array_diff($sourceColumns, $archiveColumns));

        if ($missingArchiveColumns) {
            throw new \RuntimeException(
                'Migrasi dibatalkan: tabel service_records_legacy tidak memiliki semua kolom sumber: '
                . implode(', ', $missingArchiveColumns)
                . '. Tidak ada catatan yang dipindahkan.'
            );
        }

        if (!in_array('id', $sharedColumns, true) || !in_array('service_id', $sharedColumns, true)) {
            throw new \RuntimeException(
                'Migrasi dibatalkan: struktur service_records_legacy tidak cocok; tidak ada catatan yang dipindahkan.'
            );
        }

        DB::table('service_records')
            ->select($sourceColumns)
            ->orderBy('id')
            ->chunkById(250, function ($rows) use ($sharedColumns) {
                $legacyRows = [];
                $legacyIds = [];
                $expectedServiceIds = [];

                foreach ($rows as $row) {
                    $serviceId = (string) ($row->service_id ?? '');
                    if ($this->isPositiveNumericServiceId($serviceId)) {
                        continue;
                    }

                    $record = (array) $row;
                    $legacyRows[] = array_intersect_key($record, array_flip($sharedColumns));
                    $legacyIds[] = $row->id;
                    $expectedServiceIds[(string) $row->id] = $serviceId;
                }

                if (!$legacyRows) {
                    return;
                }

                DB::transaction(function () use ($legacyRows, $legacyIds, $expectedServiceIds) {
                    DB::table('service_records_legacy')->insertOrIgnore($legacyRows);

                    $archived = DB::table('service_records_legacy')
                        ->whereIn('id', $legacyIds)
                        ->get(['id', 'service_id']);

                    $archivedIds = [];
                    foreach ($archived as $archivedRow) {
                        $key = (string) $archivedRow->id;
                        if (
                            array_key_exists($key, $expectedServiceIds) &&
                            (string) $archivedRow->service_id === $expectedServiceIds[$key]
                        ) {
                            $archivedIds[] = $archivedRow->id;
                        }
                    }

                    if (count($archivedIds) !== count($legacyIds)) {
                        throw new \RuntimeException(
                            'Migrasi dihentikan: verifikasi arsip catatan legacy gagal. Baris sumber tidak dihapus.'
                        );
                    }

                    DB::table('service_records')->whereIn('id', $archivedIds)->delete();
                });
            });
    }

    /**
     * Avoid copying a legacy VIN over a chassis number already assigned to another row.
     */
    private function assertVehicleBackfillWillNotCreateDuplicateChassisNumbers(): void
    {
        if (
            !Schema::hasTable('vehicles') ||
            !Schema::hasColumn('vehicles', 'vin') ||
            !Schema::hasColumn('vehicles', 'chassis_number')
        ) {
            return;
        }

        $conflict = DB::table('vehicles as source')
            ->join('vehicles as target', 'target.chassis_number', '=', 'source.vin')
            ->where(function ($query) {
                $query->whereNull('source.chassis_number')
                    ->orWhere('source.chassis_number', '');
            })
            ->whereNotNull('source.vin')
            ->where('source.vin', '!=', '')
            ->whereColumn('target.id', '!=', 'source.id')
            ->exists();

        if ($conflict) {
            throw new \RuntimeException(
                'Migrasi dibatalkan: nilai VIN lama akan bertabrakan dengan chassis_number kendaraan lain. '
                . 'Selesaikan konflik nomor rangka terlebih dahulu; tidak ada kolom yang dihapus.'
            );
        }
    }
};
