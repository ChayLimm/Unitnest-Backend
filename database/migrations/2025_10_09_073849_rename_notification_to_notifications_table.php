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
        // Rename the table from 'notification' to 'notifications'
        Schema::rename('notification', 'notifications');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back to original
        Schema::rename('notifications', 'notification');
    }
};