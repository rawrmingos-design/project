<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addTenantId('pembelians', ['tenant_id', 'order_id']);
        $this->addTenantId('pembayarans', ['tenant_id', 'order_id']);
        $this->addTenantId('deposits', ['tenant_id', 'order_id']);
        $this->addTenantId('users', ['tenant_id', 'username']);
    }

    public function down(): void
    {
        foreach (['pembelians', 'pembayarans', 'deposits', 'users'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign(["tenant_id"]);
                $table->dropIndex($this->tenantIndexName($tableName));
                $table->dropColumn('tenant_id');
            });
        }
    }

    private function addTenantId(string $tableName, array $indexColumns): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'tenant_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexColumns) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->nullOnDelete();

            $table->index($indexColumns, $this->tenantIndexName($tableName));
        });
    }

    private function tenantIndexName(string $tableName): string
    {
        return $tableName . '_tenant_lookup_index';
    }
};
