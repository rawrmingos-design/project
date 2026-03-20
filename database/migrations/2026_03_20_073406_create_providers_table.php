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
        if (Schema::hasTable('providers')) {
            return;
        }

Schema::create('providers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('api_username')->nullable();
            $table->string('api_key')->nullable()->comment('Encrypted or plain? Plain for now as per existing project pattern');
            $table->string('api_endpoint')->nullable();
            $table->decimal('balance', 16)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_check_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
