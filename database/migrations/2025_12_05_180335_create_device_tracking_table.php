<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            
            // Información del dispositivo
            $table->string('device_ip', 45);
            $table->string('device_mac', 17)->nullable();
            $table->string('device_name', 100)->nullable();
            $table->enum('device_type', ['laptop', 'phone', 'tablet', 'other'])->nullable();
            
            // Última actividad
            $table->dateTime('last_seen');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            
            // Estado
            $table->boolean('is_connected')->default(true);
            
            $table->timestamps();
            
            $table->unique(['client_id', 'device_ip'], 'unique_device');
            $table->index('last_seen');
            $table->index('is_connected');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tracking');
    }
};