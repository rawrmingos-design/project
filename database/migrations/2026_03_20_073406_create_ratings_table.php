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
        if (Schema::hasTable('ratings')) {
            return;
        }

Schema::create('ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('rating_id');
            $table->string('kategori_id', 225);
            $table->string('bintang');
            $table->string('comment');
            $table->string('username', 225);
            $table->string('layanan', 225);
            $table->string('no_pembeli', 225);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
