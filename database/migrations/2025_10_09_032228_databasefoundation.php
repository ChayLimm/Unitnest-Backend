<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use League\CommonMark\Reference\Reference;
use PHPUnit\Framework\Constraint\Constraint;

return new class extends Migration
{
    public function up()
    {
        // bakong_account table
        Schema::create('bakong_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('users');
            $table->string('bakong_id');
            $table->string('bakong_name');
            $table->string('bakong_location');
            $table->timestamps(); 
            $table->softDeletes(); 

        });

        // building table
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('users');
            $table->string('name');
            $table->string('address');
            $table->string('image_url')->nullable();
            $table->integer('floor')->nullable();
            $table->integer('unit')->nullable();

            $table->timestamps(); 
            $table->softDeletes(); 


        });

        // settings table
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('general_rules')->nullable();
            $table->string('contract_rules')->nullable();
            $table->decimal('khr_currency',10,0)->nullable();
            
            $table->timestamps(); 
        });

        // roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name');

            $table->timestamps(); 
            $table->softDeletes(); 
        });

        // room_types table
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('room_type_name');
            $table->string("description")->nullable();

            $table->timestamps(); 
            $table->softDeletes(); 
        });

        // rooms table 
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings');
            $table->foreignId('room_type_id')->constrained('room_types');
            $table->decimal('price',10,2)->nullable();
            $table->string('barcode')->nullable();
            $table->string('room_number');
            $table->string('floor')->nullable();
            $table->string('status')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
        // contacts table
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms');
            $table->foreignId('tenant_id')->constrained('users');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('deposit_amount',10,2)->nullable();
            $table->string('status');

            $table->timestamps(); 
            $table->softDeletes(); 
        });

        // services table
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('unit_price',10,2)->nullable();
            $table->string('description')->nullable();

            $table->timestamps(); 
            $table->softDeletes(); 
        });

        // consumption table
        Schema::create('consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms');
            $table->foreignId('service_id')->constrained('services');
            $table->decimal('end_reading',10,4)->nullable();
            $table->string('photo_url')->nullable();
            $table->decimal('consumption',10,4)->nullable();

            $table->timestamps(); 
            $table->softDeletes(); 
        });
          // transactions 
          Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->json('payload');

            $table->timestamps(); 
            $table->softDeletes(); 
        });

        // payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users');
            $table->foreignId('landlord_id')->constrained('users');
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->foreignId('room_id')->constrained('rooms');

            $table->string('status')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('md5')->nullable();

            $table->timestamps(); 
            $table->softDeletes(); 
        });

        // payment_items table
        Schema::create('payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments');
            $table->foreignId('service_id')->constrained('services');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('subtotal',10,2)->nullable();
            
            $table->timestamps(); 
            $table->softDeletes(); 
        });

        // notification table
        Schema::create('notification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments');
            $table->string('notification_type')->nullable();
            $table->string('email')->nullable();
            $table->boolean('read')->nullable();

            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification');
        Schema::dropIfExists('payment_items');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('consumptions');
        Schema::dropIfExists('services');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('bakong_accounts');
        Schema::dropIfExists('transactions');
    }
};

