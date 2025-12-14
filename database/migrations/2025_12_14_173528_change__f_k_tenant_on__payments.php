<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // 1. Drop the existing foreign key constraint to users
            // You might need to check the exact constraint name
            $table->dropForeign(['tenant_id']);
            
            // 2. Add new foreign key constraint to tenants table
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Reverse: drop tenants foreign key, add users foreign key back
            $table->dropForeign(['tenant_id']);
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('users');
        });
    }
};