<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            
            // Pago
            $table->dateTime('payment_date');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'card', 'other']);
            
            // Referencia
            $table->string('transaction_reference', 100)->nullable();
            
            // Banco (si es transferencia)
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('payment_date');
            $table->index(['client_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};