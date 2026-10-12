#!/usr/bin/env bash
#
# Fixture siklus hidup tenant untuk E2E (STAGING ONLY).
#
# Dipakai `tests/e2e/tenant-lifecycle.spec.js` lewat aksi `setup|activate|suspend|
# teardown`. Spec menjalankannya via `execFileSync` sehingga siklus penuh
# (suspend → bayar → aktif → suspend lagi) benar-benar terjadi di browser, bukan
# disimulasikan.
#
# Kenapa ssh dan bukan route khusus test: menambah rute yang bisa mengubah status
# tenant ke dalam aplikasi berarti menyiapkan pintu belakang di produksi juga.
# Staging sudah punya pintu yang benar — shell.
#
# Tenant contoh dibuat khusus (`e2e-lifecycle`) dan dihapus di `teardown`. Tenant
# `toko` (id=1) dan tenant 2 milik staging TIDAK disentuh.
#
# Pemakaian:
#   scripts/e2e/tenant-lifecycle-fixture.sh setup      # cetak token + subdomain
#   scripts/e2e/tenant-lifecycle-fixture.sh activate
#   scripts/e2e/tenant-lifecycle-fixture.sh suspend
#   scripts/e2e/tenant-lifecycle-fixture.sh teardown
#
set -euo pipefail

TENANT_SUBDOMAIN="${E2E_TENANT_SUBDOMAIN:-e2e-lifecycle}"
SSH_TARGET="${E2E_TENANT_SSH_TARGET:-fahmi@207.180.19.121}"
SSH_PORT="${E2E_TENANT_SSH_PORT:-2030}"
CONTAINER="${E2E_TENANT_CONTAINER:-testing-app}"

action="${1:-}"

ssh_run() {
    ssh -o StrictHostKeyChecking=no -o ConnectTimeout=20 -p "$SSH_PORT" "$SSH_TARGET" \
        "docker exec $CONTAINER php artisan tinker --execute=\"$1\"" 2>&1
}

# Tinker non-interaktif memuat fitur project yang tidak dipercaya; itu hanya
# peringatan dan tidak mengubah keluaran.
bersihkan() {
    grep -v 'Restricted Mode' || true
}

case "$action" in
setup)
    ssh_run "
\\\$sub = '$TENANT_SUBDOMAIN';
App\\Models\\Tenant::query()->where('subdomain', \\\$sub)->get()->each(function (\\\$t) {
    App\\Models\\Subscription::query()->where('tenant_id', \\\$t->id)->get()
        ->each(fn (\\\$s) => App\\Models\\SubscriptionInvoice::query()->where('subscription_id', \\\$s->id)->delete());
    App\\Models\\Subscription::query()->where('tenant_id', \\\$t->id)->delete();
    \\\$t->delete();
});
\\\$owner = App\\Models\\User::query()->create([
    'name' => 'E2E Lifecycle Owner',
    'email' => 'e2e-lifecycle@example.test',
    'password' => bcrypt(str()->random(32)),
    'role' => 'Gold',
]);
\\\$tenant = App\\Models\\Tenant::query()->create([
    'owner_user_id' => \\\$owner->id,
    'name' => 'E2E Lifecycle Store',
    'subdomain' => \\\$sub,
    'tier' => 'starter',
    'status' => App\\Models\\Tenant::STATUS_SUSPENDED,
]);
\\\$sub2 = App\\Models\\Subscription::query()->create([
    'tenant_id' => \\\$tenant->id,
    'tier' => 'starter',
    'price' => 500000,
    'status' => App\\Models\\Subscription::STATUS_ACTIVE,
    'gateway_ref' => 'SUB-E2E-' . strtoupper(uniqid()),
    'current_period_start' => now()->subMonth(),
    'current_period_end' => now()->subDays(5),
]);
\\\$inv = App\\Models\\SubscriptionInvoice::query()->create([
    'subscription_id' => \\\$sub2->id,
    'amount' => 510000,
    'status' => App\\Models\\SubscriptionInvoice::STATUS_PENDING,
    'gateway' => 'duitku',
    'gateway_ref' => App\\Models\\SubscriptionInvoice::freshGatewayRef(),
    'due_date' => now()->subDay(),
    'metadata' => ['source' => 'billing_renewal', 'kind' => 'renewal', 'late_fee' => 10000],
]);
echo 'TOKEN=' . \\\$inv->ensurePublicToken() . PHP_EOL;
echo 'SUBDOMAIN=' . \\\$tenant->subdomain . PHP_EOL;
echo 'STATUS=' . \\\$tenant->status . PHP_EOL;
" | bersihkan | grep -E '^(TOKEN|SUBDOMAIN|STATUS)='
    ;;

activate)
    ssh_run "
\\\$t = App\\Models\\Tenant::query()->where('subdomain', '$TENANT_SUBDOMAIN')->firstOrFail();
\\\$t->forceFill(['status' => App\\Models\\Tenant::STATUS_ACTIVE])->save();
echo 'STATUS=' . \\\$t->fresh()->status . PHP_EOL;
" | bersihkan | grep -E '^STATUS='
    ;;

suspend)
    ssh_run "
\\\$t = App\\Models\\Tenant::query()->where('subdomain', '$TENANT_SUBDOMAIN')->firstOrFail();
\\\$t->forceFill(['status' => App\\Models\\Tenant::STATUS_SUSPENDED])->save();
echo 'STATUS=' . \\\$t->fresh()->status . PHP_EOL;
" | bersihkan | grep -E '^STATUS='
    ;;

teardown)
    ssh_run "
\\\$sub = '$TENANT_SUBDOMAIN';
\\\$ids = App\\Models\\Tenant::query()->where('subdomain', \\\$sub)->pluck('id');
foreach (App\\Models\\Subscription::query()->whereIn('tenant_id', \\\$ids)->get() as \\\$s) {
    App\\Models\\SubscriptionInvoice::query()->where('subscription_id', \\\$s->id)->delete();
    \\\$s->delete();
}
App\\Models\\Tenant::query()->whereIn('id', \\\$ids)->get()->each->delete();
App\\Models\\User::query()->where('email', 'e2e-lifecycle@example.test')->delete();
echo 'SISA=' . App\\Models\\Tenant::query()->where('subdomain', \\\$sub)->count() . PHP_EOL;
" | bersihkan | grep -E '^SISA='
    ;;

*)
    echo "Pemakaian: $0 {setup|activate|suspend|teardown}" >&2
    exit 2
    ;;
esac
