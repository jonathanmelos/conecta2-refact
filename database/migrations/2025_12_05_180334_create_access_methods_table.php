<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            
            // Métodos de acceso
            $table->string('access_code', 10)->unique()->nullable();
            $table->string('rfid_card', 50)->unique()->nullable();
            $table->string('face_recognition_id', 100)->unique()->nullable();
            
            // Estado
            $table->boolean('is_active')->default(true);
            $table->dateTime('expires_at')->nullable();
            
            $table->timestamps();
            
            $table->index('client_id');
            $table->index('access_code');
            $table->index('rfid_card');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_methods');
    }
};