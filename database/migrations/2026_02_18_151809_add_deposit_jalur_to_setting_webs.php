<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->enum('deposit_jalur', ['duitku', 'tripay', 'tokopay'])->default('duitku')->after('duitku_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn('deposit_jalur');
        });
    }
};
