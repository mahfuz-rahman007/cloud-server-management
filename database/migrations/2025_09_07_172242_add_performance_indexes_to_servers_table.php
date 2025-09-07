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
        Schema::table('servers', function (Blueprint $table) {
            // Performance indexes for common operations
            
            // Index for default sorting (created_at DESC)
            $table->index('created_at', 'servers_created_at_index');
            
            // Index for updated_at sorting
            $table->index('updated_at', 'servers_updated_at_index');
            
            // Composite index for filtering + sorting
            $table->index(['status', 'created_at'], 'servers_status_created_at_index');
            $table->index(['provider', 'created_at'], 'servers_provider_created_at_index');
            
            // Index for search operations
            $table->index(['name'], 'servers_name_search_index');
            
            // Composite indexes for common filter combinations
            $table->index(['status', 'provider'], 'servers_status_provider_index');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            // Drop performance indexes
            $table->dropIndex('servers_created_at_index');
            $table->dropIndex('servers_updated_at_index');
            $table->dropIndex('servers_status_created_at_index');
            $table->dropIndex('servers_provider_created_at_index');
            $table->dropIndex('servers_name_search_index');
            $table->dropIndex('servers_status_provider_index');
        });
    }
};
