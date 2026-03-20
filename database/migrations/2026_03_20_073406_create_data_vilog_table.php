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
        if (Schema::hasTable('data_vilog')) {
            return;
        }

Schema::create('data_vilog', function (Blueprint $table) {
            $table->string('userid', 225);
            $table->string('serverid', 225);
            $table->string('email', 225);
            $table->string('password');
            $table->string('pilihlogin');
            $table->text('status_vilog');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_vilog');
    }
};
