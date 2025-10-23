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
        // Make payload nullable
        Schema::table('transactions', function (Blueprint $table) {
            $table->json('payload')->nullable()->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            // Drop existing foreign keys first
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['landlord_id']);
            $table->dropForeign(['transaction_id']);
            $table->dropForeign(['room_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            // Then alter and re-add constraints as nullable
            $table->foreignId('tenant_id')->nullable()->change();
            $table->foreign('tenant_id')->references('id')->on('users')->nullOnDelete();

            $table->foreignId('landlord_id')->nullable()->change();
            $table->foreign('landlord_id')->references('id')->on('users')->nullOnDelete();

            $table->foreignId('transaction_id')->nullable()->change();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();

            $table->foreignId('room_id')->nullable()->change();
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();

            // Add deeplink field after md5
            $table->string('deep_link')->nullable()->after('md5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->json('payload')->nullable(false)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('deep_link');
        });
    }
};
