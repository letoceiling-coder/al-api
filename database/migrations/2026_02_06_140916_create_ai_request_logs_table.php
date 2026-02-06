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
        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('request_id')->unique();
            $table->string('provider', 20); // 'gemini', 'openai'
            $table->string('model', 100); // 'gpt-4', 'gemini-1.5-pro'
            
            // Request data
            $table->integer('prompt_length')->nullable();
            $table->boolean('has_files')->default(false);
            $table->integer('file_count')->default(0);
            
            // Tokens and cost
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('total_tokens')->nullable();
            $table->decimal('estimated_cost', 10, 6)->nullable(); // in USD
            
            // Performance
            $table->float('processing_time')->nullable(); // in seconds
            
            // Status
            $table->string('status', 20); // 'success', 'error', 'rate_limited'
            $table->text('error_message')->nullable();
            
            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('api_key_source', 20)->nullable(); // 'internal', 'user_request', 'user_saved'
            
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['provider', 'model']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_request_logs');
    }
};
