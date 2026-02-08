<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trendagent_regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Код региона (spb, msk)');
            $table->string('name', 255);
            $table->string('external_id', 255)->nullable()->comment('ID из TrendAgent API');
            $table->timestamps();
            
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_regions');
    }
};
