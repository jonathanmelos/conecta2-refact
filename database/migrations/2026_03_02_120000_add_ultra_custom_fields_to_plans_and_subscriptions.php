<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'is_pilot')) {
                $table->boolean('is_pilot')->default(false)->after('description');
            }

            if (!Schema::hasColumn('plans', 'is_ultra_custom')) {
                $table->boolean('is_ultra_custom')->default(false)->after('is_pilot');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'is_ultra_custom')) {
                $table->boolean('is_ultra_custom')->default(false)->after('auto_renew');
            }

            if (!Schema::hasColumn('subscriptions', 'custom_cowork_hours')) {
                $table->decimal('custom_cowork_hours', 10, 2)->nullable()->after('is_ultra_custom');
            }

            if (!Schema::hasColumn('subscriptions', 'custom_meeting_room_hours')) {
                $table->decimal('custom_meeting_room_hours', 10, 2)->nullable()->after('custom_cowork_hours');
            }

            if (!Schema::hasColumn('subscriptions', 'custom_prints_included')) {
                $table->integer('custom_prints_included')->nullable()->after('custom_meeting_room_hours');
            }

            if (!Schema::hasColumn('subscriptions', 'custom_events_included')) {
                $table->integer('custom_events_included')->nullable()->after('custom_prints_included');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'custom_events_included')) {
                $table->dropColumn('custom_events_included');
            }

            if (Schema::hasColumn('subscriptions', 'custom_prints_included')) {
                $table->dropColumn('custom_prints_included');
            }

            if (Schema::hasColumn('subscriptions', 'custom_meeting_room_hours')) {
                $table->dropColumn('custom_meeting_room_hours');
            }

            if (Schema::hasColumn('subscriptions', 'custom_cowork_hours')) {
                $table->dropColumn('custom_cowork_hours');
            }

            if (Schema::hasColumn('subscriptions', 'is_ultra_custom')) {
                $table->dropColumn('is_ultra_custom');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'is_ultra_custom')) {
                $table->dropColumn('is_ultra_custom');
            }
        });
    }
};
