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
        Schema::create('user_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Limits
            $table->integer('daily_request_limit')->default(100);
            $table->integer('monthly_token_limit')->default(100000);
            $table->integer('max_file_size_mb')->default(10);
            
            // Current usage
            $table->integer('today_requests')->default(0);
            $table->date('today_reset_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_limits');
    }
};
