<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Добавляет поля для ImportDataCommand и контракта image_url (local_path -> Storage::url).
     */
    public function up(): void
    {
        Schema::table('trendagent_images', function (Blueprint $table) {
            if (!Schema::hasColumn('trendagent_images', 'mime')) {
                $table->string('mime', 100)->nullable()->after('local_path');
            }
            if (!Schema::hasColumn('trendagent_images', 'size')) {
                $table->unsignedBigInteger('size')->nullable()->after('mime');
            }
            if (!Schema::hasColumn('trendagent_images', 'hash')) {
                $table->string('hash', 64)->nullable()->after('size')->comment('SHA1 для дедупликации');
            }
            if (!Schema::hasColumn('trendagent_images', 'download_status')) {
                $table->string('download_status', 20)->default('pending')->after('hash')
                    ->comment('none|pending|ready|failed');
            }
            if (!Schema::hasColumn('trendagent_images', 'downloaded_at')) {
                $table->timestamp('downloaded_at')->nullable()->after('download_status');
            }
        });

        Schema::table('trendagent_images', function (Blueprint $table) {
            if (Schema::hasColumn('trendagent_images', 'hash')) {
                $table->index('hash', 'idx_trendagent_images_hash');
            }
            if (Schema::hasColumn('trendagent_images', 'download_status')) {
                $table->index('download_status', 'idx_trendagent_images_dl_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trendagent_images', function (Blueprint $table) {
            if (Schema::hasColumn('trendagent_images', 'hash')) {
                $table->dropIndex('idx_trendagent_images_hash');
            }
            if (Schema::hasColumn('trendagent_images', 'download_status')) {
                $table->dropIndex('idx_trendagent_images_dl_status');
            }
        });

        $columns = ['mime', 'size', 'hash', 'download_status', 'downloaded_at'];
        foreach ($columns as $col) {
            if (Schema::hasColumn('trendagent_images', $col)) {
                Schema::table('trendagent_images', fn (Blueprint $t) => $t->dropColumn($col));
            }
        }
    }
};
