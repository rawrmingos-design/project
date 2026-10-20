<?php

namespace Tests\Feature\Tenancy;

use App\Models\SettingWeb;
use App\Services\Settings\DatabaseSettingsBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Kebijakan SaaS & billing dibaca dari panel, bukan .env.
 *
 * Yang diuji adalah perilaku yang membuat fitur ini berguna bagi pemilik
 * platform: mengubah saklar di panel langsung berlaku, sementara kolom yang
 * belum pernah diisi TIDAK boleh menimpa nilai .env yang sedang dipakai.
 */
class SaasPolicySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // TestCase menjalankan console, jadi bridge tidak dipasang otomatis
        // (lihat AppServiceProvider). Setiap tes memanggilnya eksplisit supaya
        // guard "sekali per proses" tidak membuat hasil tes saling bocor.
        config(['bot.database_settings_applied' => false]);
    }

    private function applyBridge(): void
    {
        config(['bot.database_settings_applied' => false]);
        app(DatabaseSettingsBridge::class)->apply();
    }

    /**
     * `setting_webs` punya puluhan kolom NOT NULL tanpa default dan tidak punya
     * factory, jadi barisnya dibangun dari skema: kolom NOT NULL yang belum
     * disebut diisi nilai netral sesuai tipenya. Cara ini tahan terhadap
     * penambahan kolom baru di migrasi lain.
     */
    private function makeSettings(array $values): SettingWeb
    {
        $columns = Schema::getColumns('setting_webs');
        $row = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            if (in_array($name, ['id', 'created_at', 'updated_at'], true)) {
                continue;
            }

            if (array_key_exists($name, $values)) {
                $row[$name] = $values[$name];
                continue;
            }

            if ($column['nullable'] || $column['default'] !== null) {
                continue;
            }

            $row[$name] = match (true) {
                str_contains($column['type_name'], 'int') => 0,
                str_contains($column['type_name'], 'bool') => false,
                str_contains($column['type_name'], 'float'), str_contains($column['type_name'], 'double'), str_contains($column['type_name'], 'decimal') => 0,
                default => '',
            };
        }

        $id = DB::table('setting_webs')->insertGetId($row + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SettingWeb::query()->findOrFail($id);
    }

    public function test_panel_menyalakan_saas_membuka_gerbang_tenant(): void
    {
        config(['tenancy.disabled' => true]);
        $this->makeSettings(['saas_enabled' => true]);

        $this->applyBridge();

        $this->assertFalse((bool) config('tenancy.disabled'), 'SaaS menyala → tenancy.disabled harus false');
    }

    public function test_panel_mematikan_saas_menutup_gerbang_tenant(): void
    {
        config(['tenancy.disabled' => false]);
        $this->makeSettings(['saas_enabled' => false]);

        $this->applyBridge();

        $this->assertTrue((bool) config('tenancy.disabled'), 'SaaS mati → tenancy.disabled harus true');
    }

    public function test_kolom_kosong_tidak_menimpa_env_yang_sedang_dipakai(): void
    {
        // Kondisi nyata sebelum admin pernah membuka halaman setelan.
        config([
            'tenancy.disabled' => false,
            'billing.renewal_enabled' => true,
            'billing.grace_days' => 5,
        ]);

        $this->makeSettings([
            'saas_enabled' => null,
            'subdomain_guard_enabled' => null,
            'billing_renewal_enabled' => null,
            'billing_grace_days' => null,
        ]);

        $this->applyBridge();

        $this->assertFalse((bool) config('tenancy.disabled'), 'SaaS tetap seperti .env');
        $this->assertTrue((bool) config('billing.renewal_enabled'), 'billing tetap seperti .env');
        $this->assertSame(5, (int) config('billing.grace_days'), 'tenggang tetap seperti .env');
    }

    public function test_nilai_billing_dari_panel_menimpa_env(): void
    {
        config([
            'billing.renewal_enabled' => true,
            'billing.reminder_days_before' => 3,
            'billing.grace_days' => 3,
            'billing.late_fee' => 10000,
            'billing.payment_link_lifetime_minutes' => 1440,
        ]);

        $this->makeSettings([
            'billing_renewal_enabled' => false,
            'billing_reminder_days_before' => 7,
            'billing_grace_days' => 10,
            'billing_late_fee' => 25000,
            'billing_payment_link_minutes' => 2880,
        ]);

        $this->applyBridge();

        $this->assertFalse((bool) config('billing.renewal_enabled'));
        $this->assertSame(7, (int) config('billing.reminder_days_before'));
        $this->assertSame(10, (int) config('billing.grace_days'));
        $this->assertSame(25000, (int) config('billing.late_fee'));
        $this->assertSame(2880, (int) config('billing.payment_link_lifetime_minutes'));
    }

    public function test_saklar_billing_dari_panel_menghentikan_cron_perpanjangan(): void
    {
        // Inti keluhan: cron dijalankan `schedule:run` (console), tempat bridge
        // tidak terpasang otomatis. Command harus membaca panel sendiri.
        config(['billing.renewal_enabled' => true]);
        $this->makeSettings(['billing_renewal_enabled' => false]);

        $this->artisan('billing:renew-subscriptions')
            ->expectsOutputToContain('Penagihan otomatis dimatikan')
            ->assertSuccessful();
    }

    public function test_perintah_suspend_juga_menghormati_saklar_panel(): void
    {
        config(['billing.renewal_enabled' => true]);
        $this->makeSettings(['billing_renewal_enabled' => false]);

        $this->artisan('billing:suspend-overdue')
            ->expectsOutputToContain('Penagihan otomatis dimatikan')
            ->assertSuccessful();
    }

    public function test_migrasi_mengisi_nilai_awal_dari_config_yang_berlaku(): void
    {
        // Backfill: baris setelan yang ada harus langsung terisi angka efektif,
        // supaya panel tidak menampilkan kosong saat pertama kali dibuka.
        $settings = $this->makeSettings([]);

        $migration = require database_path('migrations/2026_10_20_090000_add_saas_policy_columns_to_setting_webs.php');
        $migration->up();

        $settings->refresh();

        $this->assertNotNull($settings->saas_enabled);
        $this->assertSame(
            ! (bool) config('tenancy.disabled', true),
            (bool) $settings->saas_enabled,
        );
        $this->assertNotNull($settings->billing_reminder_days_before);
        $this->assertNotNull($settings->billing_grace_days);
        $this->assertNotNull($settings->billing_late_fee);
    }

    public function test_backfill_tidak_menimpa_nilai_yang_sudah_diatur_admin(): void
    {
        $settings = $this->makeSettings(['billing_grace_days' => 21]);

        $migration = require database_path('migrations/2026_10_20_090000_add_saas_policy_columns_to_setting_webs.php');
        $migration->up();

        $this->assertSame(21, (int) $settings->fresh()->billing_grace_days);
    }
}
