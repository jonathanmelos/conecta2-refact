<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 13)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('ruc', 20)->nullable();
            
            // Estados
            $table->enum('client_status', ['active', 'deleted'])->default('active');
            $table->enum('subscription_status', ['active', 'expired'])->default('expired');
            
            // Suscripción actual
            $table->unsignedBigInteger('current_subscription_id')->nullable();            
            $table->timestamps();
            
            $table->index('document_number');
            $table->index('client_status');
            $table->index('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};