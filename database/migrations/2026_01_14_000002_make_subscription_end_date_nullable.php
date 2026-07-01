<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('subscriptions', 'end_date')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->date('end_date')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'end_date')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->date('end_date')->nullable(false)->change();
            });
        }
    }
};
