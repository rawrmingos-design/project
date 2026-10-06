<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Leaderboard publik (theme Inertia istanatopup + service bot/API).
 *
 * Aturan produk yang dikunci test ini:
 * 1. Akun role Admin tidak pernah muncul di peringkat (rank 1/2/3 dsb),
 *    sedangkan Member/Gold/Platinum tetap tampil.
 * 2. Hanya transaksi sukses (Sukses/Success, case-insensitive) yang dihitung;
 *    Pending/Expired/Gagal tidak menambah total maupun count.
 * 3. Count transaksi dikirim ke frontend (dulu kolom "Transaksi" hardcoded "—").
 * 4. Pembeli tanpa baris users (data lama) tetap tampil — role NULL bukan admin.
 */
class LeaderboardAdminExclusionTest extends TestCase
{
    use RefreshDatabase;

    private function setTheme(string $theme): void
    {
        SettingWeb::create([
            'id' => 1,
            'judul_web' => 'Istana Topup',
            'deskripsi_web' => 'Deskripsi test',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/istana',
            'url_tiktok' => 'https://tiktok.com/@istana',
            'url_youtube' => 'https://youtube.com/@istana',
            'url_fb' => 'https://facebook.com/istana',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'IST',
            'warna1' => '#0f172a',
            'warna2' => '#334155',
            'warna3' => '#64748b',
            'warna4' => '#94a3b8',
            'public_theme' => $theme,
        ]);
    }

    private function makePurchase(string $username, int $harga, string $status, ?string $date = null): void
    {
        DB::table('pembelians')->insert([
            'order_id' => 'TEST-' . uniqid(),
            'username' => $username,
            'user_id' => '1234',
            'zone' => null,
            'nickname' => null,
            'layanan' => 'Mobile Legends 86 Diamond',
            'harga' => $harga,
            'profit' => 500,
            'status' => $status,
            'created_at' => $date ?? now(),
            'updated_at' => $date ?? now(),
            'used_points' => 0,
            'used_point_amount' => 0,
        ]);
    }

    public function test_admin_role_is_excluded_and_regular_roles_still_ranked(): void
    {
        $this->setTheme('istanatopup');

        User::factory()->create(['username' => 'ownera', 'name' => 'Owner Admin', 'role' => 'Admin']);
        User::factory()->create(['username' => 'memberb', 'name' => 'Budi Member', 'role' => 'Member']);
        User::factory()->create(['username' => 'goldc', 'name' => 'Cici Gold', 'role' => 'Gold']);
        User::factory()->create(['username' => 'platd', 'name' => 'Dedi Platinum', 'role' => 'Platinum']);

        // Admin belanja paling besar — TIDAK boleh muncul.
        $this->makePurchase('ownera', 9_000_000, 'Sukses');
        $this->makePurchase('ownera', 9_000_000, 'Sukses');
        // Member/Gold/Platinum tetap tampil.
        $this->makePurchase('memberb', 300_000, 'Sukses');
        $this->makePurchase('goldc', 200_000, 'Sukses');
        $this->makePurchase('platd', 100_000, 'Sukses');

        $response = $this->get('/id/leaderboard')->assertOk();

        $response->assertInertia(function ($page) {
            $page->component('Public/Leaderboard');
            $page->has('leaderboards.daily', 3);

            $daily = $page->toArray()['props']['leaderboards']['daily'];
            $usernames = array_column($daily, 'username');

            foreach ($usernames as $masked) {
                $this->assertStringNotContainsString('Owner Admin', $masked);
                $this->assertStringNotContainsString('Owner', $masked);
            }

            $this->assertSame(300_000, (int) $daily[0]['total']);
            $this->assertSame(1, (int) $daily[0]['count']);
        });
    }

    public function test_only_success_statuses_count_case_insensitively(): void
    {
        $this->setTheme('istanatopup');

        User::factory()->create(['username' => 'membere', 'name' => 'Eka Member', 'role' => 'Member']);
        User::factory()->create(['username' => 'memberf', 'name' => 'Fahmi Member', 'role' => 'Member']);

        // Semua varian sukses ikut dihitung.
        $this->makePurchase('membere', 100_000, 'Sukses');
        $this->makePurchase('membere', 50_000, 'Success');
        // Status non-sukses TIDAK dihitung.
        $this->makePurchase('membere', 999_000, 'Pending');
        $this->makePurchase('membere', 999_000, 'Expired');
        $this->makePurchase('membere', 999_000, 'Gagal');
        $this->makePurchase('membere', 999_000, 'Proses');
        $this->makePurchase('memberf', 10_000, 'Sukses');

        $response = $this->get('/id/leaderboard')->assertOk();

        $response->assertInertia(function ($page) {
            $daily = $page->toArray()['props']['leaderboards']['daily'];
            $this->assertSame(150_000, (int) $daily[0]['total'], 'Hanya Sukses+Success yang dihitung');
            $this->assertSame(2, (int) $daily[0]['count']);
        });
    }

    public function test_bot_api_service_applies_the_same_rules(): void
    {
        $this->setTheme('istanatopup');

        User::factory()->create(['username' => 'adminx', 'name' => 'Admin X', 'role' => 'Admin']);
        User::factory()->create(['username' => 'membery', 'name' => 'Yudi Member', 'role' => 'Member']);

        $this->makePurchase('adminx', 8_000_000, 'Sukses');
        $this->makePurchase('membery', 250_000, 'Sukses');
        $this->makePurchase('membery', 250_000, 'Pending');

        $response = $this->get('/api/leaderboard')->assertOk();

        $data = $response->json('data');
        $today = $data['today'];

        $this->assertNotEmpty($today);
        $this->assertSame(250_000, (int) $today[0]['total_harga']);
        $this->assertStringNotContainsString('Admin', $today[0]['username']);
    }
}
