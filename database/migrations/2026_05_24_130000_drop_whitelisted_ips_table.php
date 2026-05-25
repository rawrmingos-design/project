<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('whitelisted_ips');
    }

    public function down(): void
    {
        if (Schema::hasTable('whitelisted_ips')) {
            return;
        }

        Schema::create('whitelisted_ips', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address')->unique();
            $table->timestamps();
        });
    }
};
