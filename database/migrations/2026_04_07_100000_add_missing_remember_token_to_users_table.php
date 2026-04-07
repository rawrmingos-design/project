<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'remember_token')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $column = $table->string('remember_token', 100)->nullable();

            if (Schema::hasColumn('users', 'email')) {
                $column->after('email');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'remember_token')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('remember_token');
        });
    }
};
