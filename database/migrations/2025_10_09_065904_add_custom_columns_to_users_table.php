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
        Schema::table('users', function (Blueprint $table) {
            // Add your custom columns after the default ones
            $table->foreignId('role_id')->nullable()->after('remember_token')->constrained('roles');
            $table->bigInteger('telegram_id')->nullable()->unique()->after('role_id');
            $table->string('username')->nullable()->after('telegram_id');
            $table->string('phonenumber')->nullable()->after('username');
            $table->bigInteger('identify_id')->nullable()->unique()->after('phonenumber');
            $table->string('profile_image_url')->nullable()->after('identify_id');
            $table->string('identify_image_url')->nullable()->after('profile_image_url');
            $table->softDeletes()->after('updated_at');
            
            // Add indexes for better performance
            $table->index('role_id');
            $table->index('telegram_id');
            $table->index('identify_id');
            $table->index('phonenumber');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['role_id']);
            
            // Drop your custom columns
            $table->dropColumn([
                'role_id',
                'telegram_id',
                'username',
                'phonenumber',
                'identify_id',
                'profile_image_url',
                'identify_image_url'
            ]);
            
            // Drop soft deletes
            $table->dropSoftDeletes();
            
            // Drop indexes
            $table->dropIndex(['role_id']);
            $table->dropIndex(['telegram_id']);
            $table->dropIndex(['identify_id']);
            $table->dropIndex(['phonenumber']);
        });
    }
};