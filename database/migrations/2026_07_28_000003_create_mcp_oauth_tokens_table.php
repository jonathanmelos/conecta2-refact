<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token_hash', 64)->unique();
            $table->string('refresh_token_hash', 64)->nullable()->unique();
            $table->string('client_id', 64);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('scopes');
            $table->timestamp('access_expires_at');
            $table->timestamp('refresh_expires_at')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('client_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_oauth_tokens');
    }
};
