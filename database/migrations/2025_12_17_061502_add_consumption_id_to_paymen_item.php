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
        Schema::table('payment_items', function (Blueprint $table) {
            // Add nullable consumption_id column with foreign key constraint
            $table->foreignId('consumption_id')
                  ->nullable()  // This makes the column nullable
                  ->constrained('consumptions');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_items', function (Blueprint $table) {
            // Drop the foreign key and the column
            $table->dropForeign(['consumption_id']);
            $table->dropColumn('consumption_id');
        });
    }
};