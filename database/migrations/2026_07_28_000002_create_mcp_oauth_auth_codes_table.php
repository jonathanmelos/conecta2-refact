<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_oauth_auth_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code_hash', 64)->unique();
            $table->string('client_id', 64);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('redirect_uri');
            $table->json('scopes');
            $table->string('code_challenge', 255);
            $table->string('code_challenge_method', 10)->default('S256');
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_oauth_auth_codes');
    }
};
