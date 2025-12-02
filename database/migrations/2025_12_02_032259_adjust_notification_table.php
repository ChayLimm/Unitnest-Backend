<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // PostgreSQL syntax to make column nullable
            DB::statement('ALTER TABLE notifications ALTER COLUMN payment_id DROP NOT NULL');
            
            // Add new columns
            $table->json('payload')->nullable();
            $table->string('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Make payment_id NOT NULL again
            DB::statement('ALTER TABLE notifications ALTER COLUMN payment_id SET NOT NULL');
            
            // Drop the added columns
            $table->dropColumn('payload');
            $table->dropColumn('status');
        });
    }
};