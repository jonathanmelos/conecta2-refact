<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_members', function (Blueprint $table) {
            $table->boolean('is_default_cowork')->default(false)->after('is_default');
            $table->boolean('is_default_meeting_room')->default(false)->after('is_default_cowork');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_members', function (Blueprint $table) {
            $table->dropColumn(['is_default_cowork', 'is_default_meeting_room']);
        });
    }
};
