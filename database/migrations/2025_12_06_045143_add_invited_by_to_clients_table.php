<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('invited_by_client_id')->nullable()->after('current_subscription_id');
            $table->foreign('invited_by_client_id')->references('id')->on('clients')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['invited_by_client_id']);
            $table->dropColumn('invited_by_client_id');
        });
    }
};