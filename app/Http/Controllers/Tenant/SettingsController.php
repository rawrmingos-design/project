<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->get();
        abort_unless($tenant, 404);
        abort_unless((int) ($request->user()?->id) === (int) $tenant->owner_user_id, 403);

        return view('tenant.settings', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->get();
        abort_unless($tenant, 404);
        abort_unless((int) ($request->user()?->id) === (int) $tenant->owner_user_id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30'],
            'markup_type' => ['required', 'in:percent,fixed'],
            'markup_value' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'theme' => [
                'primary_color' => $validated['primary_color'],
                'accent_color' => $validated['accent_color'],
            ],
            'settings' => [
                'contact_whatsapp' => $validated['contact_whatsapp'] ?? null,
            ],
            'margin_config' => [
                'markup_type' => $validated['markup_type'],
                'markup_value' => (float) $validated['markup_value'],
            ],
        ]);

        return redirect()->route('tenant.settings')->with('success', 'Pengaturan toko berhasil disimpan.');
    }
}
