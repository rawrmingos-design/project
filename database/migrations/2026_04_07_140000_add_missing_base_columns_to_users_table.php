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

        Schema::table('users', function (Blueprint $table): void {
            $this->addStringColumn($table, 'name');
            $this->addStringColumn($table, 'username', default: 'anonim');
            $this->addStringColumn($table, 'referral_code');
            $this->addStringColumn($table, 'uplink');
            $this->addStringColumn($table, 'password');
            $this->addStringColumn($table, 'email');
            $this->addStringColumn($table, 'api_key');
            $this->addStringColumn($table, 'no_wa');
            $this->addBigIntegerColumn($table, 'balance');
            $this->addStringColumn($table, 'role', default: 'Member');
            $this->addUnsignedBigIntegerColumn($table, 'point_balance', 0);
            $this->addStringColumn($table, 'affiliate_status', default: 'inactive');
            $this->addStringColumn($table, 'idgame', 225);
            $this->addIntegerColumn($table, 'servergame');
            $this->addStringColumn($table, 'idgame2', 2225);
            $this->addStringColumn($table, 'otp');
            $this->addStringColumn($table, 'google2fa_secret', 2255);
            $this->addTextColumn($table, 'two_factor_secret');
            $this->addTextColumn($table, 'two_factor_recovery_codes');

            if (! Schema::hasColumn('users', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Compatibility migration for legacy imports. Do not drop columns that may
        // already belong to production schemas.
    }

    private function addStringColumn(
        Blueprint $table,
        string $column,
        int $length = 255,
        ?string $default = null
    ): void {
        if (Schema::hasColumn('users', $column)) {
            return;
        }

        $definition = $table->string($column, $length)->nullable();

        if ($default !== null) {
            $definition->default($default);
        }
    }

    private function addTextColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn('users', $column)) {
            $table->text($column)->nullable();
        }
    }

    private function addIntegerColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn('users', $column)) {
            $table->integer($column)->nullable();
        }
    }

    private function addBigIntegerColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn('users', $column)) {
            $table->bigInteger($column)->nullable();
        }
    }

    private function addUnsignedBigIntegerColumn(Blueprint $table, string $column, int $default): void
    {
        if (! Schema::hasColumn('users', $column)) {
            $table->unsignedBigInteger($column)->default($default);
        }
    }
};
