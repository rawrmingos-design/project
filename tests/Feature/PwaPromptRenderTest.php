<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PwaPromptRenderTest extends TestCase
{
    public function test_prompt_markup_renders_on_allowed_home_path(): void
    {
        $html = $this->renderPromptForPath('/id');

        $this->assertStringContainsString('id="pwa-install-card"', $html);
        $this->assertStringContainsString('Install Sekarang', $html);
    }

    public function test_prompt_markup_does_not_render_on_blocked_invoice_path(): void
    {
        $html = $this->renderPromptForPath('/id/invoices');

        $this->assertStringNotContainsString('id="pwa-install-card"', $html);
        $this->assertStringNotContainsString('Install Sekarang', $html);
    }

    public function test_prompt_markup_does_not_render_on_blocked_reseller_path(): void
    {
        $html = $this->renderPromptForPath('/id/reseller/dashboard');

        $this->assertStringNotContainsString('id="pwa-install-card"', $html);
        $this->assertStringNotContainsString('Install Sekarang', $html);
    }

    private function renderPromptForPath(string $path): string
    {
        $request = Request::create($path, 'GET');
        app()->instance('request', $request);

        return Blade::render("@include('template.id.partials.pwa-install-prompt')", [
            'config' => null,
        ]);
    }
}
