<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('chassis_number', 100)->nullable()->unique();
            $table->string('owner_wallet_address')->nullable();
            $table->string('sync_status', 20)->default('PENDING');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['chassis_number']);

            $table->dropColumn([
                'chassis_number',
                'owner_wallet_address',
                'sync_status',
            ]);
        });
}
};
