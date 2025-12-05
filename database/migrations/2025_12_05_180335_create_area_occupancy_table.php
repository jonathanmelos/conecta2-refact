<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_occupancy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            
            // Tiempo en el área
            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();
            
            // Detección
            $table->enum('detected_by', ['manual', 'sensor', 'ip_tracking']);
            
            // Información del dispositivo (para tracking por IP)
            $table->string('device_ip', 45)->nullable();
            $table->string('device_mac', 17)->nullable();
            
            // Sensor IoT
            $table->string('sensor_device_id', 100)->nullable();
            
            $table->timestamps();
            
            $table->index(['area_id', 'check_out']); // Para ocupación actual
            $table->index(['client_id', 'area_id', 'check_in']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_occupancy');
    }
};