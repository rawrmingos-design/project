<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResellerApplicationRequest;
use App\Services\CaptchaRuntimeResolver;
use App\Services\ResellerApplicationEligibilityService;
use App\Services\ResellerApplicationSubmissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegistryController extends Controller
{
    public function __construct(
        private readonly ResellerApplicationEligibilityService $eligibilityService,
        private readonly ResellerApplicationSubmissionService $submissionService,
        private readonly CaptchaRuntimeResolver $captchaRuntimeResolver,
    ) {
    }

    public function showForm(Request $request)
    {
        $user = $request->user();
        
        // Fetch settings from database (including WhatsApp and public branding)
        $settings = \DB::table('setting_webs')->where('id', 1)->first();
        $supportWhatsappUrl = $settings?->url_wa ?? null;
        $captchaRuntime = $this->captchaRuntimeResolver->resolve();
        $captchaConfig = [
            'site_key' => $captchaRuntime['site_key'],
            'enabled' => $captchaRuntime['is_active'],
            'bypass' => $captchaRuntime['bypass'],
            'misconfigured' => $captchaRuntime['enabled']
                && ! $captchaRuntime['bypass']
                && ! $captchaRuntime['is_active'],
        ];
        
        // If user is authenticated, load their existing application data
        // Frontend will handle showing appropriate banner (Member vs Reseller)
        // No eligibility checks here - anyone can VIEW the form
        if ($user) {
            // Load existing application if exists (for resubmission)
            $existingApplication = $user->resellerApplication;
            $existingDocuments = $user->resellerDocuments->keyBy('document_type');
            
            return Inertia::render('Reseller/Registry', [
                'current_user' => [
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->no_wa ?? null,
                    'role' => $user->role,
                ],
                'captcha' => $captchaConfig,
                'existing_application' => $existingApplication ? [
                    'business_name' => $existingApplication->business_name,
                    'business_url' => $existingApplication->business_url,
                    'estimated_transactions' => $existingApplication->estimated_transactions,
                    'application_reason' => $existingApplication->application_reason,
                    'status' => $existingApplication->status,
                    'applied_at' => $existingApplication->applied_at?->toISOString(),
                ] : null,
                'existing_documents' => [
                    'identity' => $existingDocuments->get('identity')?->file_url,
                    'selfie' => $existingDocuments->get('selfie')?->file_url,
                    'business_proof' => $existingDocuments->get('business_proof')?->file_url,
                ],
                // Pass flash data for success state
                'submission_success' => session('submission_success', false),
                'success_message' => session('success_message', ''),
                'support_whatsapp_url' => $supportWhatsappUrl,
                'logo_header' => $settings?->logo_header,
                'app_name' => config('app.name', 'VoucherPro'),
            ]);
        }
        
        // Guest user - show form with null user data
        return Inertia::render('Reseller/Registry', [
            'current_user' => null,
            'captcha' => $captchaConfig,
            'existing_application' => null,
            'existing_documents' => [
                'identity' => null,
                'selfie' => null,
                'business_proof' => null,
            ],
            // Pass flash data for success state (guests won't have success, but keep consistent)
            'submission_success' => session('submission_success', false),
            'success_message' => session('success_message', ''),
            'support_whatsapp_url' => $supportWhatsappUrl,
            'logo_header' => $settings?->logo_header,
            'app_name' => config('app.name', 'VoucherPro'),
        ]);
    }

    public function submit(ResellerApplicationRequest $request)
    {
        // Validation 1: Ensure user is authenticated (redundant check, but safe)
        if (! auth()->check()) {
            return back()
                ->withErrors(['auth' => 'Anda harus login sebagai Member untuk mendaftar sebagai reseller.'])
                ->withInput();
        }
        
        $user = $request->user();
        
        // Validation 2: Ensure user is NOT already a reseller
        if (in_array($user->role, ['Gold', 'Platinum'], true)) {
            return redirect()
                ->route('reseller.dashboard')
                ->with('flash_info', 'Anda sudah memiliki akses Reseller Hub.');
        }
        
        // Validation 3: Check eligibility (account age, pending apps, cooldown period)
        $eligibility = $this->eligibilityService->evaluate($user);
        
        if (! $eligibility['can_apply']) {
            return back()
                ->withErrors(['eligibility' => implode(' ', $eligibility['reasons'])])
                ->withInput();
        }
        
        try {
            $this->submissionService->submit(
                user: $user,
                applicationData: $request->only([
                    'business_name',
                    'business_url',
                    'estimated_monthly_transactions',
                    'application_reason',
                ]),
                documents: [
                    'identity' => $request->file('identity'),
                    'selfie' => $request->file('selfie'),
                    'business_proof' => $request->file('business_proof'),
                ],
                ipAddress: $request->ip(),
            );
            
            return redirect()
                ->route('reseller.registry.form')
                ->with('submission_success', true)
                ->with('success_message', 'Pengajuan reseller berhasil dikirim dan sedang dalam proses review. Tim kami akan meninjau dalam 1-3 hari kerja.');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            report($e);
            
            return back()
                ->with('flash_error', 'Terjadi kesalahan saat mengirim pengajuan. Silakan coba lagi.')
                ->withInput();
        }
    }
}
