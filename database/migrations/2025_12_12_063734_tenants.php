<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('telegram_id', 20)->nullable(); // Fixed: removed duplicate
            $table->string('identify_id', 20)->nullable();
            $table->string('profile_image_url', 255)->nullable(); // Increased length for URLs
            $table->string('identify_image_url', 255)->nullable(); // Increased length for URLs
            $table->json('emergency_contact')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['last_name', 'first_name']);
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};