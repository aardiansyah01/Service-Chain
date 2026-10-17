<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockchain_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('entity_type', 50);
            $table->string('entity_id', 100);

            $table->string('transaction_hash', 66)
                ->nullable()
                ->unique();

            $table->string('contract_address', 42)->nullable();
            $table->unsignedBigInteger('block_number')->nullable();

            $table->string('status', 20)->default('PENDING');

            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockchain_transactions');
    }
};