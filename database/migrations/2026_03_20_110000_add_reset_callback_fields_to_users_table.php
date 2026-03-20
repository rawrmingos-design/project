<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('reset_callback_enabled')->default(false)->after('api_key');
            $table->string('reset_callback_url')->nullable()->after('reset_callback_enabled');
            $table->text('reset_callback_secret')->nullable()->after('reset_callback_url');
            $table->string('reset_callback_signing_algorithm')->default('sha256')->after('reset_callback_secret');
            $table->unsignedSmallInteger('reset_callback_version')->default(1)->after('reset_callback_signing_algorithm');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'reset_callback_enabled',
                'reset_callback_url',
                'reset_callback_secret',
                'reset_callback_signing_algorithm',
                'reset_callback_version',
            ]);
        });
    }
};
