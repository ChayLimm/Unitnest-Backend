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
        Schema::create("receipts", function(Blueprint $table) {
            $table->id();
            $table->string('receipt_name')->nullable();
            $table->foreignId('payment_id')->constrained()->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
        
        Schema::table('telegram_bots', function(Blueprint $table) {
            $table->string('first_name')->nullable();
        });
        
        Schema::table('users', function(Blueprint $table) {
            $table->string('device_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop("receipts"); 
        
        Schema::table('telegram_bots', function(Blueprint $table) {
            $table->dropColumn('first_name');
        });
        
        Schema::table('users', function(Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
};