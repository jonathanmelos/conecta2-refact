<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->string('service_name', 100);
            $table->enum('service_type', ['print', 'meeting_room_extra', 'cowork_extra', 'event', 'other']);
            
            // Precio
            $table->decimal('price', 10, 2);
            $table->string('unit', 50); // 'por hoja', 'por hora', 'por evento'
            
            // Vigencia
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('service_type');
            $table->index(['valid_from', 'valid_until']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};