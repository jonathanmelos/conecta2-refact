<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            
            // Ítem
            $table->string('description', 255);
            $table->enum('item_type', ['subscription', 'extra_hours', 'prints', 'event', 'other']);
            
            // Referencia (opcional)
            $table->foreignId('usage_record_id')->nullable()->constrained('usage_records')->nullOnDelete();
            
            // Cantidad y precio
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            $table->timestamp('created_at')->nullable();
            
            $table->index('invoice_id');
            $table->index('item_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};