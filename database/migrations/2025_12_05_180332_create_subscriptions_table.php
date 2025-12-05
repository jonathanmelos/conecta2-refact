<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            
            // Financiero
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('setup_fee_paid', 10, 2)->default(0);
            $table->decimal('deposit_paid', 10, 2)->default(0);
            $table->decimal('discount_applied', 10, 2)->default(0);
            $table->decimal('total_paid', 10, 2)->default(0);
            
            // Facturación
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annual'])->default('monthly');
            $table->date('next_billing_date')->nullable();
            $table->boolean('auto_renew')->default(true);
            
            $table->timestamps();
            
            $table->index(['client_id', 'status']);
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};