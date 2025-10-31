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
        Schema::table('services', function (Blueprint $table) {
            $table->string('unit_name')->nullable();
            $table->string('service_name')->nullable();
        });

        Schema::create('room_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained(); // Fixed: added ()
            $table->foreignId('service_id')->constrained(); // Fixed: added ()
            $table->timestamps();
        });

        Schema::table('payment_items', function (Blueprint $table) {
            $table->dropForeign(['service_id']);           
            $table->foreignId('service_id')->nullable()->change();
            $table->foreign('service_id')->references('id')->on('services');
            $table->string('service_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse payment_items changes first
        Schema::table('payment_items', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['service_id']);
            
            // Make column non-nullable again
            $table->foreignId('service_id')->nullable(false)->change();
            
            // Re-add foreign key constraint
            $table->foreign('service_id')->references('id')->on('services');
        });

        // Drop the pivot table
        Schema::dropIfExists('room_service');

        // Remove columns from services table
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['unit_name', 'service_name']); // Fixed: drop both columns
        });
    }
};

