<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('workshop_name');
            $table->string('owner_name');
            $table->text('address');
            $table->string('city');
            $table->string('phone', 30);
            $table->string('license_number')->unique();
            $table->string('wallet_address')->nullable();
            $table->enum('status', ['PENDING', 'ACTIVE', 'REJECTED', 'INACTIVE'])
                ->default('PENDING');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
