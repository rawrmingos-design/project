<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Support\PublicThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicThemeRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Pastikan env kembali ke kondisi test (testing) antar kasus.
        app()->forgetScopedInstances();

        parent::tearDown();
    }

    public function test_istanatopup_theme_is_registered(): void
    {
        $this->assertArrayHasKey('istanatopup', PublicThemeRegistry::options());
        $this->assertSame('istanatopup', PublicThemeRegistry::normalize('istanatopup'));
        $this->assertSame('istanatopup', PublicThemeRegistry::normalize('ISTANATOPUP '));
    }

    public function test_unknown_theme_falls_back_to_default(): void
    {
        $this->assertSame(PublicThemeRegistry::DEFAULT, PublicThemeRegistry::normalize('tidak-ada'));
        $this->assertSame(PublicThemeRegistry::DEFAULT, PublicThemeRegistry::normalize(null));
    }

    public function test_preview_only_theme_resolves_on_non_production(): void
    {
        $this->assertFalse(app()->environment('production'));

        $this->assertSame(
            'istanatopup',
            PublicThemeRegistry::resolveForEnvironment('istanatopup')
        );
    }

    public function test_preview_only_theme_falls_back_to_default_on_production(): void
    {
        config(['app.env' => 'production']);

        $this->assertTrue(app()->environment('production'));
        $this->assertSame(
            PublicThemeRegistry::DEFAULT,
            PublicThemeRegistry::resolveForEnvironment('istanatopup')
        );
    }

    public function test_bangjeff_removes_legacy_redirect_only_when_active(): void
    {
        SettingWeb::query()->forceFill([
            'id' => 1,
            'public_theme' => 'bangjeff',
        ])->save();

        $this->assertSame('bangjeff', PublicThemeRegistry::resolveForEnvironment('bangjeff'));
    }
}
