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
        Schema::table('ai_request_logs', function (Blueprint $table) {
            // Request metadata
            $table->integer('request_size_bytes')->nullable()->after('file_count')->comment('Request payload size in bytes');
            $table->integer('response_size_bytes')->nullable()->after('request_size_bytes')->comment('Response payload size in bytes');
            
            // Timing breakdown
            $table->float('queue_time_ms', 8, 3)->nullable()->after('processing_time')->comment('Time spent in queue (milliseconds)');
            $table->float('ai_response_time_ms', 8, 3)->nullable()->after('queue_time_ms')->comment('AI provider response time (milliseconds)');
            $table->float('network_time_ms', 8, 3)->nullable()->after('ai_response_time_ms')->comment('Network latency (milliseconds)');
            
            // Error tracking
            $table->string('error_code', 50)->nullable()->after('status')->comment('Error code if failed');
            $table->text('error_details')->nullable()->after('error_code')->comment('Detailed error information');
            $table->string('error_type', 100)->nullable()->after('error_details')->comment('Exception class name');
            
            // File metadata
            $table->json('file_metadata')->nullable()->after('file_count')->comment('Detailed file information (sizes, types)');
            
            // Model metadata
            $table->string('model_version', 50)->nullable()->after('model')->comment('Specific model version used');
            $table->json('model_parameters_used')->nullable()->after('model_version')->comment('Actual parameters sent to model');
            
            // User context
            $table->string('user_country', 2)->nullable()->after('user_agent')->comment('User country (ISO 3166-1 alpha-2)');
            $table->string('user_timezone', 50)->nullable()->after('user_country')->comment('User timezone');
            
            // Response metadata
            $table->string('finish_reason', 50)->nullable()->after('completion_tokens')->comment('Why model stopped generating');
            $table->json('safety_ratings')->nullable()->after('finish_reason')->comment('Content safety ratings (Gemini)');
            
            // Performance flags
            $table->boolean('was_cached')->default(false)->after('api_key_source')->comment('Was response served from cache');
            $table->boolean('used_streaming')->default(false)->after('was_cached')->comment('Was streaming used');
            $table->boolean('used_multipart')->default(false)->after('used_streaming')->comment('Was multipart upload used');
            
            // Cost tracking
            $table->decimal('estimated_cost_usd', 10, 6)->nullable()->after('total_tokens')->comment('Estimated cost in USD');
            
            // Add indexes for analytics
            $table->index(['created_at', 'provider'], 'idx_created_provider');
            $table->index(['created_at', 'model'], 'idx_created_model');
            $table->index(['status', 'error_code'], 'idx_status_error');
            $table->index('finish_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_request_logs', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_created_provider');
            $table->dropIndex('idx_created_model');
            $table->dropIndex('idx_status_error');
            $table->dropIndex(['finish_reason']);
            
            // Drop columns
            $table->dropColumn([
                'request_size_bytes',
                'response_size_bytes',
                'queue_time_ms',
                'ai_response_time_ms',
                'network_time_ms',
                'error_code',
                'error_details',
                'error_type',
                'file_metadata',
                'model_version',
                'model_parameters_used',
                'user_country',
                'user_timezone',
                'finish_reason',
                'safety_ratings',
                'was_cached',
                'used_streaming',
                'used_multipart',
                'estimated_cost_usd',
            ]);
        });
    }
};
