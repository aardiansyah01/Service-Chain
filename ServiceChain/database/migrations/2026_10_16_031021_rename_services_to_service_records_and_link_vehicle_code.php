<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lepas foreign key lama: services.vehicle_id -> vehicles.id
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->string('vehicle_code')->nullable();
        });

        // Salin ID kendaraan yang sebenarnya dari tabel vehicles.
        $records = DB::table('services as s')
            ->join('vehicles as v', 's.vehicle_id', '=', 'v.id')
            ->select('s.id', 'v.vehicle_id as canonical_vehicle_id')
            ->get();

        foreach ($records as $record) {
            DB::table('services')
                ->where('id', $record->id)
                ->update([
                    'vehicle_code' => $record->canonical_vehicle_id,
                ]);
        }

        // Ganti vehicle_id numerik dengan vehicle_id dari blockchain.
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('vehicle_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('vehicle_code', 'vehicle_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('vehicle_id')->nullable(false)->change();
        });

        // Gunakan nama tabel sesuai rancangan final.
        Schema::rename('services', 'service_records');

        // Hubungkan dengan vehicles.vehicle_id.
        Schema::table('service_records', function (Blueprint $table) {
            $table->foreign('vehicle_id')
                ->references('vehicle_id')
                ->on('vehicles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_records', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->unsignedBigInteger('vehicle_local_id')->nullable();
        });

        // Kembalikan relasi ke ID numerik lokal.
        $records = DB::table('service_records as s')
            ->join('vehicles as v', 's.vehicle_id', '=', 'v.vehicle_id')
            ->select('s.id', 'v.id as local_vehicle_id')
            ->get();

        foreach ($records as $record) {
            DB::table('service_records')
                ->where('id', $record->id)
                ->update([
                    'vehicle_local_id' => $record->local_vehicle_id,
                ]);
        }

        Schema::table('service_records', function (Blueprint $table) {
            $table->dropColumn('vehicle_id');
        });

        Schema::table('service_records', function (Blueprint $table) {
            $table->renameColumn('vehicle_local_id', 'vehicle_id');
        });

        Schema::table('service_records', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id')
                ->nullable(false)
                ->change();
        });

        Schema::rename('service_records', 'services');

        Schema::table('services', function (Blueprint $table) {
            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->cascadeOnDelete();
        });
    }
};