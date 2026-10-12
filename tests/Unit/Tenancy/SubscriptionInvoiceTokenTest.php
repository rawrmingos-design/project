<?php

namespace Tests\Unit\Tenancy;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * D13.1: token publik invoice.
 *
 * Yang diuji di sini adalah sifat yang membuat tautan email/WA tetap hidup —
 * bukan sekadar "kolomnya ada".
 */
class SubscriptionInvoiceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_berisi_32_byte_acak_dalam_hex(): void
    {
        $token = SubscriptionInvoice::generatePublicToken();

        $this->assertSame(64, strlen($token), '32 byte hex = 64 karakter.');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function test_token_selalu_berbeda(): void
    {
        $tokens = collect(range(1, 50))
            ->map(fn () => SubscriptionInvoice::generatePublicToken())
            ->unique();

        $this->assertCount(50, $tokens, 'Token tidak boleh berulang.');
    }

    public function test_ensure_public_token_idempoten(): void
    {
        // Ini inti D13.1: tautan yang sudah terkirim tidak boleh mati hanya
        // karena halaman dibuka lagi.
        $invoice = $this->makeInvoice();

        $pertama = $invoice->ensurePublicToken();
        $kedua = $invoice->fresh()->ensurePublicToken();

        $this->assertSame($pertama, $kedua, 'Token harus tetap, bukan dibuat ulang.');
    }

    public function test_revoke_public_token_mengosongkan_token(): void
    {
        $invoice = $this->makeInvoice();
        $invoice->ensurePublicToken();

        $invoice->revokePublicToken();

        $this->assertNull($invoice->fresh()->public_token);
    }

    public function test_gateway_ref_tidak_pernah_dipakai_sebagai_token(): void
    {
        // gateway_ref memuat timestamp + 6 karakter acak (~30 bit) dan BERUBAH
        // tiap tautan bayar diregenerasi — memakainya sebagai token akan
        // mematikan tautan lama dan bisa ditebak.
        $invoice = $this->makeInvoice();
        $invoice->ensurePublicToken();

        $this->assertNotSame($invoice->gateway_ref, $invoice->public_token);
        $this->assertStringNotContainsString('SUB-', (string) $invoice->public_token);
    }

    public function test_instance_basi_tidak_menimpa_token_yang_sudah_terkirim(): void
    {
        // Notifikasi job memakai instance model hasil eager-load. Kalau kolomnya
        // sudah terisi tapi instance-nya masih melihat null, implementasi yang
        // menilai dari atribut memori akan MENIMPA token itu — dan tautan yang
        // terlanjur dikirim lewat email/WA langsung mati.
        $invoice = $this->makeInvoice();

        $tokenAsli = $invoice->ensurePublicToken();

        $basi = SubscriptionInvoice::query()->find($invoice->id);
        $basi->setRawAttributes([...$basi->getAttributes(), 'public_token' => null], true);

        $this->assertSame($tokenAsli, $basi->ensurePublicToken());
        $this->assertSame($tokenAsli, $invoice->fresh()->public_token);
    }

    public function test_ensure_public_token_tidak_menyentuh_gateway_ref(): void
    {
        $invoice = $this->makeInvoice();
        $ref = $invoice->gateway_ref;

        $invoice->ensurePublicToken();

        $this->assertSame($ref, $invoice->fresh()->gateway_ref);
    }

    private function makeInvoice(): SubscriptionInvoice
    {
        $owner = User::factory()->create(['role' => 'Gold']);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Token',
            'subdomain' => 'toko-token-' . uniqid(),
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_ACTIVE,
            'gateway_ref' => 'SUB-TOKEN-' . strtoupper(uniqid()),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addDays(5),
        ]);

        return SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 500000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDays(2),
        ]);
    }
}
