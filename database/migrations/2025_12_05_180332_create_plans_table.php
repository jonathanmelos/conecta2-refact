<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            
            // Servicios incluidos
            $table->integer('cowork_hours')->default(0);
            $table->integer('meeting_room_hours')->default(0);
            $table->integer('prints_included')->default(0);
            $table->integer('events_included')->default(0);
            
            // Precios
            $table->decimal('price', 10, 2);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->decimal('deposit_required', 10, 2)->default(0);
            
            // Costos
            $table->decimal('operational_cost', 10, 2)->default(0);
            
            // Descuentos
            $table->decimal('discount_percentage', 5, 2)->default(0);
            
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};