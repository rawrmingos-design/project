<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembelians')) {
            return;
        }

        if (
            Schema::hasColumn('pembelians', 'base_order_id') &&
            Schema::hasColumn('pembelians', 'invoice_version') &&
            Schema::hasColumn('pembelians', 'display_order_id') &&
            Schema::hasColumn('pembelians', 'active_attempt_reference')
        ) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            if (! Schema::hasColumn('pembelians', 'base_order_id')) {
                $table->string('base_order_id')->nullable()->after('order_id');
            }
            if (! Schema::hasColumn('pembelians', 'invoice_version')) {
                $table->unsignedInteger('invoice_version')->default(0)->after('base_order_id');
            }
            if (! Schema::hasColumn('pembelians', 'display_order_id')) {
                $table->string('display_order_id')->nullable()->after('invoice_version');
            }
            if (! Schema::hasColumn('pembelians', 'active_layanan_id')) {
                $table->unsignedBigInteger('active_layanan_id')->nullable()->after('layanan');
            }
            if (! Schema::hasColumn('pembelians', 'active_provider_code')) {
                $table->string('active_provider_code')->nullable()->after('active_layanan_id');
            }
            if (! Schema::hasColumn('pembelians', 'active_provider_sku')) {
                $table->string('active_provider_sku')->nullable()->after('active_provider_code');
            }
            if (! Schema::hasColumn('pembelians', 'active_attempt_token')) {
                $table->string('active_attempt_token')->nullable()->after('active_provider_sku');
            }
            if (! Schema::hasColumn('pembelians', 'active_attempt_reference')) {
                $table->string('active_attempt_reference')->nullable()->after('active_attempt_token');
            }
            if (! Schema::hasColumn('pembelians', 'reset_status')) {
                $table->string('reset_status')->default('none')->after('active_attempt_reference');
            }
            if (! Schema::hasColumn('pembelians', 'reset_count')) {
                $table->unsignedInteger('reset_count')->default(0)->after('reset_status');
            }
            if (! Schema::hasColumn('pembelians', 'reset_requested_by')) {
                $table->unsignedBigInteger('reset_requested_by')->nullable()->after('reset_count');
            }
            if (! Schema::hasColumn('pembelians', 'reset_requested_at')) {
                $table->timestamp('reset_requested_at')->nullable()->after('reset_requested_by');
            }
            if (! Schema::hasColumn('pembelians', 'reset_reason')) {
                $table->text('reset_reason')->nullable()->after('reset_requested_at');
            }
        });

        Schema::table('pembelians', function (Blueprint $table) {
            if (! $this->indexExists('pembelians', 'pembelians_base_order_id_index')) {
                $table->index('base_order_id');
            }
            if (! $this->indexExists('pembelians', 'pembelians_base_order_id_invoice_version_index')) {
                $table->index(['base_order_id', 'invoice_version']);
            }
            if (! $this->indexExists('pembelians', 'pembelians_display_order_id_index')) {
                $table->index('display_order_id');
            }
            if (! $this->indexExists('pembelians', 'pembelians_active_layanan_id_index')) {
                $table->index('active_layanan_id');
            }
            if (! $this->indexExists('pembelians', 'pembelians_active_attempt_token_index')) {
                $table->index('active_attempt_token');
            }
            if (! $this->indexExists('pembelians', 'pembelians_active_attempt_reference_index')) {
                $table->index('active_attempt_reference');
            }
        });

        DB::table('pembelians')->update([
            'base_order_id' => DB::raw('COALESCE(base_order_id, order_id)'),
            'invoice_version' => DB::raw('COALESCE(invoice_version, 0)'),
            'display_order_id' => DB::raw('COALESCE(display_order_id, order_id)'),
            'active_attempt_reference' => DB::raw('COALESCE(active_attempt_reference, order_id)'),
            'reset_status' => DB::raw("COALESCE(reset_status, 'none')"),
            'reset_count' => DB::raw('COALESCE(reset_count, 0)'),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembelians')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            if ($this->indexExists('pembelians', 'pembelians_base_order_id_index')) {
                $table->dropIndex(['base_order_id']);
            }
            if ($this->indexExists('pembelians', 'pembelians_base_order_id_invoice_version_index')) {
                $table->dropIndex(['base_order_id', 'invoice_version']);
            }
            if ($this->indexExists('pembelians', 'pembelians_display_order_id_index')) {
                $table->dropIndex(['display_order_id']);
            }
            if ($this->indexExists('pembelians', 'pembelians_active_layanan_id_index')) {
                $table->dropIndex(['active_layanan_id']);
            }
            if ($this->indexExists('pembelians', 'pembelians_active_attempt_token_index')) {
                $table->dropIndex(['active_attempt_token']);
            }
            if ($this->indexExists('pembelians', 'pembelians_active_attempt_reference_index')) {
                $table->dropIndex(['active_attempt_reference']);
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('pembelians', 'base_order_id') ? 'base_order_id' : null,
                Schema::hasColumn('pembelians', 'invoice_version') ? 'invoice_version' : null,
                Schema::hasColumn('pembelians', 'display_order_id') ? 'display_order_id' : null,
                Schema::hasColumn('pembelians', 'active_layanan_id') ? 'active_layanan_id' : null,
                Schema::hasColumn('pembelians', 'active_provider_code') ? 'active_provider_code' : null,
                Schema::hasColumn('pembelians', 'active_provider_sku') ? 'active_provider_sku' : null,
                Schema::hasColumn('pembelians', 'active_attempt_token') ? 'active_attempt_token' : null,
                Schema::hasColumn('pembelians', 'active_attempt_reference') ? 'active_attempt_reference' : null,
                Schema::hasColumn('pembelians', 'reset_status') ? 'reset_status' : null,
                Schema::hasColumn('pembelians', 'reset_count') ? 'reset_count' : null,
                Schema::hasColumn('pembelians', 'reset_requested_by') ? 'reset_requested_by' : null,
                Schema::hasColumn('pembelians', 'reset_requested_at') ? 'reset_requested_at' : null,
                Schema::hasColumn('pembelians', 'reset_reason') ? 'reset_reason' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return match (DB::getDriverName()) {
            'mysql' => DB::selectOne("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]) !== null,
            'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))->contains(fn ($row) => (($row->name ?? null) === $index)),
            default => false,
        };
    }
};
