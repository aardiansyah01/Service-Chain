<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('owner_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('receipt_cid')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('service_hash', 66)->nullable();
            $table->string('transaction_hash', 66)->nullable();
            $table->string('sync_status', 20)->default('PENDING');
        });

        // Isi owner_id dari pemilik kendaraan yang terkait
        // dengan masing-masing catatan servis lama.
        $records = DB::table('services as s')
            ->join('vehicles as v', 's.vehicle_id', '=', 'v.id')
            ->select('s.id', 'v.owner_id')
            ->get();

        foreach ($records as $record) {
            DB::table('services')
                ->where('id', $record->id)
                ->update(['owner_id' => $record->owner_id]);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);

            $table->dropColumn([
                'owner_id',
                'receipt_cid',
                'receipt_path',
                'service_hash',
                'transaction_hash',
                'sync_status',
            ]);
        });
    }
};