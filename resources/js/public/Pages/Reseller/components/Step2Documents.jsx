import { useEffect, useRef } from 'react';
import FileUploadZone from './FileUploadZone';

export default function Step2Documents({ 
    formData, 
    onChange, 
    onSubmit, 
    onBack, 
    errors = {},
    captcha,
    captchaToken = '',
    onCaptchaToken,
    onCaptchaExpired,
    onCaptchaWidget,
    captchaScriptReady = false,
    processing = false,
    disabled = false
}) {
    const captchaContainerRef = useRef(null);
    const captchaWidgetIdRef = useRef(null);

    useEffect(() => {
        window.onResellerCaptchaVerified = (token) => {
            onCaptchaToken?.(token || '');
        };
        window.onResellerCaptchaExpired = () => {
            onCaptchaExpired?.();
        };

        return () => {
            delete window.onResellerCaptchaVerified;
            delete window.onResellerCaptchaExpired;
            captchaWidgetIdRef.current = null;
            onCaptchaWidget?.(null);
        };
    }, [onCaptchaExpired, onCaptchaToken, onCaptchaWidget]);

    useEffect(() => {
        const captchaActive = captcha?.enabled === true && Boolean(captcha?.site_key);
        let retryTimer;
        let cancelled = false;

        const renderCaptcha = () => {
            const grecaptcha = typeof window !== 'undefined' ? window.grecaptcha : null;
            const container = captchaContainerRef.current;

            if (cancelled || !captchaActive || !grecaptcha?.ready || !grecaptcha.render || !container) {
                return Boolean(cancelled || !captchaActive);
            }

            if (captchaWidgetIdRef.current !== null || container.childElementCount > 0) {
                return true;
            }

            grecaptcha.ready(() => {
                if (cancelled || captchaWidgetIdRef.current !== null || !captchaContainerRef.current) {
                    return;
                }

                const widgetId = grecaptcha.render(captchaContainerRef.current, {
                    sitekey: captcha.site_key,
                    theme: 'dark',
                    callback: window.onResellerCaptchaVerified,
                    'expired-callback': window.onResellerCaptchaExpired,
                });
                captchaWidgetIdRef.current = widgetId;
                onCaptchaWidget?.(widgetId);
            });
            return true;
        };

        if (!renderCaptcha()) {
            retryTimer = window.setInterval(() => {
                if (renderCaptcha()) {
                    window.clearInterval(retryTimer);
                }
            }, 50);
        }

        return () => {
            cancelled = true;
            if (retryTimer) {
                window.clearInterval(retryTimer);
            }
        };
    }, [captcha, captchaScriptReady, onCaptchaWidget]);

    const captchaEnabled = captcha?.enabled === true && Boolean(captcha?.site_key);

    const handleFileChange = (field, file) => {
        onChange(field, file);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Validate files
        if (!formData.identity || !formData.selfie || !formData.business_proof) {
            alert('Mohon lengkapi semua dokumen yang diperlukan.');
            return;
        }
        
        if (captchaEnabled && !captchaToken) {
            return;
        }

        onSubmit(e);
    };

    return (
        <div className="w-full max-w-3xl mx-auto relative z-10">
            {/* Header */}
            <div className="text-center mb-8">
                <h1 className="text-3xl md:text-4xl font-bold text-white mb-2 tracking-tight">
                    Upload Dokumen Verifikasi
                </h1>
                <p className="text-sm text-gray-400">
                    Lengkapi dokumen di bawah ini untuk mempercepat proses persetujuan akun Reseller Anda.
                </p>
            </div>

            {/* Form Card */}
            <div className="glass-panel rounded-xl p-6 md:p-8 shadow-lg relative overflow-hidden">
                {/* Subtle glow behind card */}
                <div className="absolute -top-32 -left-32 w-64 h-64 bg-blue-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                
                <form onSubmit={handleSubmit} className="space-y-6 flex flex-col">
                    {/* 1. Foto KTP */}
                    <FileUploadZone
                        label="1. Foto KTP Asli"
                        icon="id_card"
                        accept=".jpg,.jpeg,.png,.pdf"
                        maxSize={5}
                        file={formData.identity}
                        error={errors.identity}
                        onChange={(file) => handleFileChange('identity', file)}
                        disabled={disabled}
                    />

                    {/* 2. Foto Selfie dengan KTP */}
                    <FileUploadZone
                        label="2. Foto Selfie dengan KTP"
                        helper="Pastikan wajah dan KTP terlihat jelas"
                        icon="face"
                        accept=".jpg,.jpeg,.png"
                        maxSize={5}
                        file={formData.selfie}
                        error={errors.selfie}
                        onChange={(file) => handleFileChange('selfie', file)}
                        disabled={disabled}
                    />

                    {/* 3. Bukti Kepemilikan Toko */}
                    <FileUploadZone
                        label="3. Bukti Kepemilikan Toko"
                        helper="Screenshot profil toko / foto fisik konter"
                        icon="storefront"
                        accept=".jpg,.jpeg,.png,.pdf"
                        maxSize={5}
                        file={formData.business_proof}
                        error={errors.business_proof}
                        onChange={(file) => handleFileChange('business_proof', file)}
                        disabled={disabled}
                    />

                    {/* Captcha Section - Right above submit button */}
                    {captchaEnabled && (
                        <div className="pt-6 border-t border-white/5">
                            <label className="block text-xs font-semibold text-white mb-3 tracking-wide uppercase">
                                Verifikasi Keamanan
                            </label>
                            <div className="flex justify-center">
                                <div ref={captchaContainerRef}></div>
                            </div>
                            {!captchaToken && (
                                <p className="mt-2 text-xs text-amber-400 text-center">
                                    Selesaikan verifikasi captcha sebelum mengirim aplikasi.
                                </p>
                            )}
                            {errors['g-recaptcha-response'] && (
                                <p className="mt-2 text-xs text-red-400 text-center">
                                    {errors['g-recaptcha-response']}
                                </p>
                            )}
                        </div>
                    )}

                    {captcha?.misconfigured && (
                        <p className="pt-6 border-t border-white/5 text-xs text-amber-400 text-center">
                            Verifikasi keamanan belum tersedia. Silakan hubungi admin.
                        </p>
                    )}

                    {/* Actions */}
                    <div className="pt-6 flex flex-col-reverse md:flex-row justify-between items-center gap-4 border-t border-white/5">
                        <button
                            type="button"
                            onClick={onBack}
                            disabled={disabled || processing}
                            className="w-full md:w-auto px-6 py-3 bg-transparent border border-gray-600 text-gray-400 font-semibold rounded hover:bg-gray-800 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Kembali
                        </button>
                        <button
                            type="submit"
                            disabled={disabled || processing}
                            className="w-full md:w-auto px-6 py-3 bg-gradient-to-b from-blue-500 to-blue-600 text-white font-semibold rounded shadow-[0_4px_14px_rgba(59,130,246,0.3)] hover:shadow-[0_6px_20px_rgba(59,130,246,0.5)] transition-all flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {processing ? (
                                <>
                                    <span className="animate-spin material-symbols-outlined text-lg">refresh</span>
                                    <span>Mengirim...</span>
                                </>
                            ) : (
                                <>
                                    <span>Kirim Aplikasi</span>
                                    <span className="material-symbols-outlined text-lg">mail</span>
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
