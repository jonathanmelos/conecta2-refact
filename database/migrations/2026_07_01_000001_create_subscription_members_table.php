<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_use_cowork')->default(true);
            $table->boolean('can_use_meeting_room')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['subscription_id', 'client_id']);
            $table->index(['client_id', 'can_use_cowork']);
            $table->index(['client_id', 'can_use_meeting_room']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_members');
    }
};
