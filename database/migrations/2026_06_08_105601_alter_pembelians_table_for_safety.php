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
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('pembelians', function (Blueprint $table) {
                // Prevent integer overflow: INT (max 2.1B) → BIGINT (max 9.2 quintillion)
                $table->bigInteger('harga')->change();
                $table->bigInteger('profit')->change();
                
                // Prevent string truncation crash: VARCHAR(1000) → TEXT (max 65KB)
                $table->text('log')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('pembelians', function (Blueprint $table) {
                // Revert to original types
                $table->integer('harga')->change();
                $table->integer('profit')->change();
                $table->string('log', 1000)->nullable()->change();
            });
        }
    }
};
