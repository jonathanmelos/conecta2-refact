<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            
            // Método usado
            $table->enum('access_method', ['manual', 'pin_code', 'rfid_card', 'face_recognition']);
            $table->string('access_value', 100)->nullable();
            
            // Tipo de evento
            $table->enum('event_type', ['entry', 'exit']);
            $table->dateTime('timestamp');
            
            // Área (si aplica)
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            
            // Información del dispositivo
            $table->string('device_ip', 45)->nullable();
            $table->string('device_mac', 17)->nullable();
            $table->string('device_name', 100)->nullable();
            
            // IoT
            $table->string('iot_device_id', 100)->nullable();
            
            $table->timestamp('created_at')->nullable();
            
            $table->index('timestamp');
            $table->index(['client_id', 'timestamp']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};