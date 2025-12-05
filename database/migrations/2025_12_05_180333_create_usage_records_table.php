<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->unsignedBigInteger('area_id')->nullable();            
            $table->enum('service_type', ['cowork', 'meeting_room', 'print', 'event']);
            
            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();
            $table->integer('quantity')->default(1);
            
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->enum('registration_method', ['manual', 'automatic'])->default('manual');
            
            // Billing
            $table->boolean('is_billable')->default(false);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_charged', 10, 2)->default(0);
            $table->boolean('invoiced')->default(false);
            $table->unsignedBigInteger('invoice_item_id')->nullable();            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'check_in']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};