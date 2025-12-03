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
        //
        Schema::table('notifications', function(Blueprint $table){
            $table->foreignId('landlord_id')
                  ->nullable()  
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->integer('chat_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('notifications', function(Blueprint $table){
            // Drop foreign key first to avoid constraint errors
            $table->dropForeign(['landlord_id']);
            
            // Remove the columns
            $table->dropColumn(['landlord_id', 'chat_id']);
        });
    }
};
