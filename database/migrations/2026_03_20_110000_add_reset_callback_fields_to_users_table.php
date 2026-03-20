<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (
            Schema::hasColumn('users', 'reset_callback_enabled') &&
            Schema::hasColumn('users', 'reset_callback_url') &&
            Schema::hasColumn('users', 'reset_callback_secret') &&
            Schema::hasColumn('users', 'reset_callback_signing_algorithm') &&
            Schema::hasColumn('users', 'reset_callback_version')
        ) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'reset_callback_enabled')) {
                $table->boolean('reset_callback_enabled')->default(false)->after('api_key');
            }
            if (! Schema::hasColumn('users', 'reset_callback_url')) {
                $table->string('reset_callback_url')->nullable()->after('reset_callback_enabled');
            }
            if (! Schema::hasColumn('users', 'reset_callback_secret')) {
                $table->text('reset_callback_secret')->nullable()->after('reset_callback_url');
            }
            if (! Schema::hasColumn('users', 'reset_callback_signing_algorithm')) {
                $table->string('reset_callback_signing_algorithm')->default('sha256')->after('reset_callback_secret');
            }
            if (! Schema::hasColumn('users', 'reset_callback_version')) {
                $table->unsignedSmallInteger('reset_callback_version')->default(1)->after('reset_callback_signing_algorithm');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('users', 'reset_callback_enabled') ? 'reset_callback_enabled' : null,
                Schema::hasColumn('users', 'reset_callback_url') ? 'reset_callback_url' : null,
                Schema::hasColumn('users', 'reset_callback_secret') ? 'reset_callback_secret' : null,
                Schema::hasColumn('users', 'reset_callback_signing_algorithm') ? 'reset_callback_signing_algorithm' : null,
                Schema::hasColumn('users', 'reset_callback_version') ? 'reset_callback_version' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
