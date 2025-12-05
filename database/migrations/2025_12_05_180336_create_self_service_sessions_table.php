<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            
            // Sesión iniciada desde
            $table->enum('platform', ['web', 'ios', 'android']);
            $table->json('device_info')->nullable(); // User agent, versión app, etc.
            
            // Tiempo de sesión
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            
            // IP de conexión
            $table->string('ip_address', 45)->nullable();
            
            $table->timestamp('created_at')->nullable();
            
            $table->index(['client_id', 'started_at']);
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_sessions');
    }
};