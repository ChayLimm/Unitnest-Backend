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
        Schema::create('room_service',function(Blueprint $table){
            $table->id();
            $table->foreignId('room_id')->constrained;
            $table->foreignId('service_id')->constrained;
            $table->timestamps();
        });
        Schema::table('services', function (Blueprint $table) {
            $table->string('unit_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('room_service');
    
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('unit_name');
        });
    }
};
