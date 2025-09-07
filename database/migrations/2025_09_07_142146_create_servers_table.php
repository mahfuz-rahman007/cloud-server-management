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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('ip_address', 45)->unique();
            $table->enum('provider', ['aws', 'digitalocean', 'vultr', 'other']);
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->integer('cpu_cores')->unsigned();
            $table->integer('ram_mb')->unsigned();
            $table->integer('storage_gb')->unsigned();
            $table->timestamps();

            // Indexes for performance
            $table->index('name');
            $table->index('provider');
            $table->index('status');
            
            // Compound unique index: name unique per provider
            $table->unique(['name', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
