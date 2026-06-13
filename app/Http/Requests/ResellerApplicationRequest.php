<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResellerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $isUpdate = $this->user()?->resellerApplication?->exists ?? false;
        
        // Check if captcha bypass is enabled in settings
        $captchaBypass = \DB::table('setting_webs')
            ->where('id', 1)
            ->value('captcha_bypass') ?? false;
        
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'business_url' => ['nullable', 'url', 'max:500'],
            'estimated_monthly_transactions' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'application_reason' => ['nullable', 'string', 'max:1000'],
            
            // Documents required on first submit, optional on resubmit if already uploaded
            'identity' => [
                $isUpdate ? 'nullable' : 'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120', // 5MB
            ],
            'selfie' => [
                $isUpdate ? 'nullable' : 'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
            'business_proof' => [
                $isUpdate ? 'nullable' : 'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
            
            // reCAPTCHA v2 validation - conditional based on bypass setting
            'g-recaptcha-response' => $captchaBypass ? [] : ['required', 'captcha'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.required' => 'Nama bisnis wajib diisi.',
            'business_url.url' => 'URL bisnis harus berupa URL yang valid.',
            'identity.required' => 'Dokumen identitas (KTP/ID) wajib diunggah.',
            'identity.mimes' => 'Dokumen identitas harus berformat JPG, PNG, atau PDF.',
            'identity.max' => 'Ukuran dokumen identitas maksimal 5MB.',
            'selfie.required' => 'Foto selfie dengan identitas wajib diunggah.',
            'selfie.mimes' => 'Foto selfie harus berformat JPG atau PNG.',
            'selfie.max' => 'Ukuran foto selfie maksimal 5MB.',
            'business_proof.required' => 'Bukti bisnis wajib diunggah.',
            'business_proof.mimes' => 'Bukti bisnis harus berformat JPG, PNG, atau PDF.',
            'business_proof.max' => 'Ukuran bukti bisnis maksimal 5MB.',
            'g-recaptcha-response.required' => 'Captcha wajib diverifikasi.',
            'g-recaptcha-response.captcha' => 'Verifikasi captcha gagal. Silakan coba lagi.',
        ];
    }
}
