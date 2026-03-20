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
        if (Schema::hasTable('media_assets')) {
            return;
        }

Schema::create('media_assets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('folder')->nullable()->index();
            $table->string('alt_text')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('source_media_id')->nullable()->unique();
            $table->string('path')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
