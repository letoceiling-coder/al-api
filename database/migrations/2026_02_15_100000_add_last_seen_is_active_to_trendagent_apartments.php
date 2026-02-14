<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trendagent_apartments')) {
            return;
        }
        Schema::table('trendagent_apartments', function (Blueprint $table) {
            if (!Schema::hasColumn('trendagent_apartments', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('trendagent_apartments', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('last_seen_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('trendagent_apartments')) {
            return;
        }
        Schema::table('trendagent_apartments', function (Blueprint $table) {
            if (Schema::hasColumn('trendagent_apartments', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }
            if (Schema::hasColumn('trendagent_apartments', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
