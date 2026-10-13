<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_id')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->string('workshop_address');
            $table->string('workshop_name');
            $table->string('service_type', 100);
            $table->text('description');
            $table->date('service_date');
            $table->decimal('cost', 15, 2);
            $table->unsignedInteger('mileage');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
