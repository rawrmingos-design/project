<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AffiliateHistory;
use App\Models\SettingWeb;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AffiliatePanelV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_gets_affiliate_application_panel_props(): void
    {
        $this->createSettings();
        $user = User::factory()->create([
            'affiliate_status' => 'inactive',
            'referral_code' => null,
        ]);

        $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->get('/id/affiliate')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('component', 'Public/Affiliate')
                ->where('props.affiliate.status', 'inactive')
                ->where('props.affiliate.links.canWithdraw', false)
                ->has('props.affiliate.application.requirements')
                ->where('props.affiliate.application.allowedFilesLabel', 'Tidak perlu upload dokumen pada tahap pendaftaran awal.')
                ->etc());
    }

    public function test_pending_user_gets_requested_at_and_generated_referral_code(): void
    {
        $this->createSettings();
        $user = User::factory()->create([
            'affiliate_status' => 'pending',
            'affiliate_requested_at' => now()->subHour(),
            'referral_code' => null,
        ]);

        $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->get('/id/affiliate')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('component', 'Public/Affiliate')
                ->where('props.affiliate.status', 'pending')
                ->whereNot('props.affiliate.referralCode', '-')
                ->whereType('props.affiliate.application.lastSubmission.requestedAt', 'string')
                ->etc());

        $this->assertNotEmpty($user->refresh()->referral_code);
    }

    public function test_rejected_user_gets_review_note_and_request_link(): void
    {
        $this->createSettings();
        $review = [
            'decision' => 'rejected',
            'note' => 'Channel promosi belum jelas.',
            'reviewed_at' => now()->subDay()->toIso8601String(),
            'reviewed_by_username' => 'admin',
        ];
        $user = User::factory()->create([
            'affiliate_status' => 'rejected',
            'affiliate_application_meta' => [
                'review_history' => [$review],
                'review_last' => $review,
            ],
        ]);

        $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->get('/id/affiliate')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('component', 'Public/Affiliate')
                ->where('props.affiliate.status', 'rejected')
                ->where('props.affiliate.application.lastReview.note', 'Channel promosi belum jelas.')
                ->whereType('props.affiliate.links.request', 'string')
                ->etc());
    }

    public function test_active_affiliate_gets_dashboard_summary_downlines_and_commission_history(): void
    {
        $this->createSettings();
        $affiliate = User::factory()->create([
            'username' => 'affiliate-user',
            'affiliate_status' => 'active',
            'referral_code' => 'REF-AFF001',
            'balance' => 125000,
        ]);
        $downlineA = User::factory()->create([
            'username' => 'downline-a',
            'name' => 'Downline A',
            'uplink' => $affiliate->username,
            'created_at' => now()->subDays(2),
        ]);
        $downlineB = User::factory()->create([
            'username' => 'downline-b',
            'name' => 'Downline B',
            'uplink' => $affiliate->username,
            'created_at' => now()->subDay(),
        ]);

        AffiliateHistory::query()->create([
            'uplink_id' => $affiliate->id,
            'downlink_id' => $downlineA->id,
            'order_id' => 'AFF-ORDER-001',
            'amount' => 7000,
            'note' => 'Commission',
        ]);
        AffiliateHistory::query()->create([
            'uplink_id' => $affiliate->id,
            'downlink_id' => $downlineB->id,
            'order_id' => 'AFF-ORDER-002',
            'amount' => 9000,
            'note' => 'Commission',
        ]);
        AffiliateHistory::query()
            ->where('order_id', 'AFF-ORDER-001')
            ->update([
                'created_at' => now()->subMonthsNoOverflow(2),
                'updated_at' => now()->subMonthsNoOverflow(2),
            ]);
        AffiliateHistory::query()
            ->where('order_id', 'AFF-ORDER-002')
            ->update([
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ]);

        $this->actingAs($affiliate)
            ->withHeaders($this->inertiaHeaders())
            ->get('/id/affiliate')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('component', 'Public/Affiliate')
                ->where('props.affiliate.status', 'active')
                ->where('props.affiliate.availableBalance', 125000)
                ->where('props.affiliate.totalCommission', 16000)
                ->where('props.affiliate.commissionThisMonth', 9000)
                ->where('props.affiliate.downlineCount', 2)
                ->has('props.affiliate.recentDownlines', 2)
                ->has('props.affiliate.commissionHistory', 2)
                ->etc());
    }

    public function test_inactive_user_cannot_open_withdrawal_page(): void
    {
        $this->createSettings();
        $user = User::factory()->create([
            'affiliate_status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->get('/id/withdrawal')
            ->assertRedirect(route('dashboard'));
    }

    public function test_active_affiliate_gets_withdrawal_form_options_and_history(): void
    {
        $this->createSettings();
        $user = User::factory()->create([
            'affiliate_status' => 'active',
            'balance' => 50000,
        ]);
        Withdrawal::query()->create([
            'user_id' => $user->id,
            'rekening' => 'BCA - 1234567890 - TEST USER',
            'total_transfer' => 20000,
            'biaya_admin' => 0,
            'status' => 'success',
            'bukti_transfer' => 'storage/bukti_withdraw/proof.jpg',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->get('/id/withdrawal')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('component', 'Public/AffiliateWithdrawal')
                ->where('props.withdrawal.currentBalance', 50000)
                ->where('props.withdrawal.minimumWithdrawal', 10000)
                ->where('props.withdrawal.canSubmit', true)
                ->where('props.withdrawal.disabledReason', null)
                ->where('props.withdrawal.bankOptions.0', 'BCA')
                ->where('props.withdrawal.withdrawals.0.status.label', 'Success')
                ->has('props.withdrawal.withdrawals', 1)
                ->etc());
    }

    public function test_withdrawal_page_disables_submit_for_low_balance(): void
    {
        $this->createSettings();
        $user = User::factory()->create([
            'affiliate_status' => 'active',
            'balance' => 5000,
        ]);

        $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->get('/id/withdrawal')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('props.withdrawal.canSubmit', false)
                ->where('props.withdrawal.disabledReason', 'Saldo minimal untuk melakukan penarikan adalah Rp 10.000.')
                ->etc());
    }

    public function test_withdrawal_page_disables_submit_when_user_already_requested_today(): void
    {
        $this->createSettings();
        $user = User::factory()->create([
            'affiliate_status' => 'active',
            'balance' => 50000,
        ]);
        Withdrawal::query()->create([
            'user_id' => $user->id,
            'rekening' => 'DANA - 6281234567890 - TEST USER',
            'total_transfer' => 15000,
            'biaya_admin' => 0,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->get('/id/withdrawal')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('props.withdrawal.canSubmit', false)
                ->where('props.withdrawal.disabledReason', 'Kamu sudah melakukan penarikan hari ini. Coba lagi besok.')
                ->etc());
    }

    public function test_valid_withdrawal_request_creates_pending_row_and_deducts_balance(): void
    {
        $this->createSettings();
        $user = User::factory()->create([
            'affiliate_status' => 'active',
            'balance' => 50000,
        ]);

        $this->actingAs($user)
            ->post('/id/withdrawal', [
                'bank_destination' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'Test User',
                'amount' => 15000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'rekening' => 'BCA - 1234567890 - Test User',
            'total_transfer' => 15000,
            'status' => 'pending',
        ]);
        $this->assertSame(35000, (int) $user->refresh()->balance);
    }

    public function test_second_withdrawal_request_same_day_is_rejected(): void
    {
        $this->createSettings();
        $user = User::factory()->create([
            'affiliate_status' => 'active',
            'balance' => 50000,
        ]);
        Withdrawal::query()->create([
            'user_id' => $user->id,
            'rekening' => 'BCA - 1234567890 - TEST USER',
            'total_transfer' => 10000,
            'biaya_admin' => 0,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->from('/id/withdrawal')
            ->post('/id/withdrawal', [
                'bank_destination' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'Test User',
                'amount' => 15000,
            ])
            ->assertRedirect('/id/withdrawal')
            ->assertSessionHasErrors('amount');

        $this->assertSame(50000, (int) $user->refresh()->balance);
    }

    public function test_withdrawal_request_below_minimum_is_rejected(): void
    {
        $this->createSettings();
        $user = User::factory()->create([
            'affiliate_status' => 'active',
            'balance' => 50000,
        ]);

        $this->actingAs($user)
            ->from('/id/withdrawal')
            ->post('/id/withdrawal', [
                'bank_destination' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'Test User',
                'amount' => 5000,
            ])
            ->assertRedirect('/id/withdrawal')
            ->assertSessionHasErrors('amount');
    }

    private function inertiaHeaders(): array
    {
        $headers = ['X-Inertia' => 'true'];
        $version = app(HandleInertiaRequests::class)->version(request());

        if ($version !== null) {
            $headers['X-Inertia-Version'] = $version;
        }

        return $headers;
    }

    private function createSettings(): SettingWeb
    {
        return SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'url_wa' => 'https://wa.me/test',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'test_api',
            'warna1' => '#222222',
            'warna2' => '#d06800',
            'warna3' => '#ffa54a',
            'warna4' => '#ff8040',
            'paydisini_apikey' => 'test_paydisini',
            'tripay_api' => 'test_api_key',
            'tripay_merchant_code' => 'test_merchant',
            'tripay_private_key' => 'test_private',
            'username_digi' => 'test_digi',
            'api_key_digi' => 'test_digi_key',
            'apigames_secret' => 'secret-123',
            'apigames_merchant' => 'merchant-123',
            'vip_apiid' => 'test_vip_id',
            'vip_apikey' => 'test_vip_key',
            'apikey_bangjeff' => 'test_bangjeff_key',
            'order_prefik' => 'INV',
            'public_theme' => 'bangjeff',
        ]);
    }
}
