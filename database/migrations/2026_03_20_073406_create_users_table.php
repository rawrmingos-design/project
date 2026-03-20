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
        if (Schema::hasTable('users')) {
            return;
        }

Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('username')->nullable()->default('anonim');
            $table->string('referral_code')->nullable()->unique();
            $table->string('uplink')->nullable();
            $table->string('password');
            $table->string('email');
            $table->string('remember_token')->nullable();
            $table->string('api_key')->nullable();
            $table->string('no_wa')->nullable();
            $table->bigInteger('balance')->nullable();
            $table->enum('role', ['Admin', 'Member', 'Gold', 'Platinum']);
            $table->unsignedBigInteger('point_balance')->default(0);
            $table->enum('affiliate_status', ['inactive', 'pending', 'active', 'rejected'])->default('inactive');
            $table->string('idgame', 225)->nullable();
            $table->integer('servergame')->nullable();
            $table->string('idgame2', 2225)->nullable();
            $table->string('otp')->nullable();
            $table->string('google2fa_secret', 2255)->nullable();
            $table->timestamps();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
