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
        Schema::create('data_joki', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('order_id');
            $table->text('email_joki');
            $table->text('password_joki');
            $table->text('loginvia_joki');
            $table->string('nickname_joki', 225);
            $table->string('request_joki', 225);
            $table->text('catatan_joki');
            $table->string('tglmain_joki', 225);
            $table->string('jambooking_joki', 225);
            $table->bigInteger('qty')->nullable();
            $table->text('status_joki');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_joki');
    }
};
