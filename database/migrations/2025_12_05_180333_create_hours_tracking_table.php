<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hours_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            
            $table->enum('service_type', ['cowork', 'meeting_room']);
            
            // Horas
            $table->decimal('hours_used', 10, 2)->default(0);
            $table->decimal('total_hours_available', 10, 2);
            
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            $table->unique(['subscription_id', 'service_type'], 'unique_tracking');
            $table->index('client_id');
        });
        
        // Agregar columna calculada para horas restantes
        DB::statement('
            ALTER TABLE hours_tracking 
            ADD COLUMN hours_remaining DECIMAL(10,2) 
            GENERATED ALWAYS AS (total_hours_available - hours_used) STORED
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('hours_tracking');
    }
};