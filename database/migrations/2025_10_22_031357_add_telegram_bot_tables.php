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
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('bot_id')->unique(); // use this for the @botfather ID
            $table->string('image_url')->nullable();
            $table->string('about')->nullable();
            $table->string('description')->nullable();
            $table->string('username')->unique(); // while this to store the name
            $table->string('token')->unique();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('telegram_bots');
    }
};
