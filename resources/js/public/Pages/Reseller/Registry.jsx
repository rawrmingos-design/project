import React, { useState } from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';

// Import new multi-step components
import ProgressBar from './components/ProgressBar';
import Step1BusinessInfo from './components/Step1BusinessInfo';
import Step2Documents from './components/Step2Documents';
import SuccessTimeline from './components/SuccessTimeline';

export default function Registry({ current_user, captcha, existing_application, existing_documents, submission_success, success_message, logo_header, support_whatsapp_url, app_name }) {
    // Get page props
    const { props } = usePage();
    const submissionSuccess = submission_success || props.submission_success || false;
    const pageErrors = props.errors || {};
    
    // Compute app name once to avoid JSX expression arrays in <title>
    const appName = app_name || 'VoucherPro';
    
    // Get normalized logo from shared siteConfig (properly normalized with leading slash)
    const normalizedLogo = props.siteConfig?.logoHeader || logo_header;

    // User state detection
    const isAuthenticated = !!current_user;
    const isReseller = current_user && ['Gold', 'Platinum'].includes(current_user?.role);
    const isMember = current_user && current_user.role === 'Member';
    const canSubmit = isAuthenticated && isMember;
    const hasPendingApplication = existing_application?.status === 'pending';

    // Multi-step state
    const [currentStep, setCurrentStep] = useState(1);

    // Form data state
    const { data, setData, post, processing, errors } = useForm({
        business_name: existing_application?.business_name || '',
        business_url: existing_application?.business_url || '',
        estimated_monthly_transactions: existing_application?.estimated_transactions || '',
        application_reason: existing_application?.application_reason || '',
        identity: null,
        selfie: null,
        business_proof: null,
        'g-recaptcha-response': '',
    });

    // Handle field changes
    const handleFieldChange = (field, value) => {
        setData(field, value);
    };

    // Step 1 validation
    const validateStep1 = () => {
        const step1Errors = {};

        if (!data.business_name || data.business_name.trim() === '') {
            step1Errors.business_name = 'Nama toko wajib diisi';
        }

        if (!data.business_url || data.business_url.trim() === '') {
            step1Errors.business_url = 'Link platform wajib diisi';
        }

        return Object.keys(step1Errors).length === 0;
    };

    // Handle next step (Step 1 → Step 2)
    const handleNextStep = () => {
        if (validateStep1()) {
            setCurrentStep(2);
            window.scrollTo(0, 0);
        } else {
            alert('Mohon lengkapi semua field yang diperlukan di Step 1');
        }
    };

    // Handle back (Step 2 → Step 1)
    const handleBack = () => {
        setCurrentStep(1);
        window.scrollTo(0, 0);
    };

    // Handle final submission (from Step 2)
    const handleFinalSubmit = (e) => {
        e.preventDefault();

        if (!canSubmit) {
            console.warn('Form submission blocked: User cannot submit');
            return;
        }

        // Get captcha response from DOM (in case it's rendered)
        const captchaResponse = document.querySelector('[name="g-recaptcha-response"]')?.value || '';

        router.post('/id/reseller/registry', {
            ...data,
            'g-recaptcha-response': captchaResponse,
        }, {
            forceFormData: true,
        });
    };

    // Banner Components (preserved from original)
    const AccountInfoSection = () => (
        <section className="max-w-4xl mx-auto px-6 pb-6">
            <div className="glass-panel rounded-xl p-6 border-2 border-blue-500/30">
                <div className="flex gap-4 items-start">
                    <div className="text-4xl">👤</div>
                    <div className="flex-1">
                        <h3 className="text-xl font-bold text-white mb-2">
                            Akun Saat Ini
                        </h3>
                        <p className="text-gray-400 text-sm mb-4">
                            Informasi akun yang akan digunakan untuk registrasi reseller:
                        </p>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div className="bg-gray-800/50 rounded-lg p-3">
                                <div className="text-xs text-gray-500 mb-1">Username</div>
                                <div className="text-white font-semibold">{current_user?.username}</div>
                            </div>
                            <div className="bg-gray-800/50 rounded-lg p-3">
                                <div className="text-xs text-gray-500 mb-1">Email</div>
                                <div className="text-white font-semibold">{current_user?.email}</div>
                            </div>
                            <div className="bg-gray-800/50 rounded-lg p-3">
                                <div className="text-xs text-gray-500 mb-1">No. WhatsApp</div>
                                <div className="text-white font-semibold">{current_user?.phone || '-'}</div>
                            </div>
                            <div className="bg-gray-800/50 rounded-lg p-3">
                                <div className="text-xs text-gray-500 mb-1">Status Akun</div>
                                <div className="text-white font-semibold">{current_user?.role}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );

    const GuestBanner = () => (
        <section className="max-w-4xl mx-auto px-6 pb-8">
            <div className="glass-panel rounded-xl p-8 border-2 border-orange-500/30">
                <div className="text-center mb-6">
                    <div className="text-6xl mb-4">🔒</div>
                    <h3 className="text-2xl font-bold text-white mb-3">
                        Login Diperlukan
                    </h3>
                    <p className="text-gray-400 mb-6 max-w-lg mx-auto">
                        Anda harus login terlebih dahulu untuk mengajukan diri sebagai Reseller.
                        Form di bawah ini hanya untuk preview.
                    </p>
                </div>
                
                <div className="bg-gray-800/30 rounded-lg p-6 text-left">
                    <h4 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                        <span>📋</span> Persyaratan Pendaftaran Reseller:
                    </h4>
                    <ul className="space-y-3 text-gray-300">
                        <li className="flex gap-3">
                            <span className="text-blue-400 mt-1">✓</span>
                            <span>Harus login sebagai <strong className="text-white">Member</strong></span>
                        </li>
                        <li className="flex gap-3">
                            <span className="text-blue-400 mt-1">✓</span>
                            <span>Akun sudah aktif minimal <strong className="text-white">7 hari</strong></span>
                        </li>
                        <li className="flex gap-3">
                            <span className="text-blue-400 mt-1">✓</span>
                            <span>Memiliki informasi bisnis yang valid (nama bisnis, website/media sosial)</span>
                        </li>
                        <li className="flex gap-3">
                            <span className="text-blue-400 mt-1">✓</span>
                            <span>Upload dokumen verifikasi: <strong className="text-white">KTP, Foto Selfie dengan KTP, Bukti Kepemilikan Toko</strong></span>
                        </li>
                        {captcha?.enabled && (
                            <li className="flex gap-3">
                                <span className="text-blue-400 mt-1">✓</span>
                                <span>Menyelesaikan verifikasi captcha</span>
                            </li>
                        )}
                    </ul>
                </div>

                <div className="mt-6 text-center">
                    <a
                        href="/id/sign-in"
                        className="inline-block px-8 py-3 bg-gradient-to-b from-blue-500 to-blue-600 text-white font-semibold rounded-lg hover:shadow-lg hover:scale-105 transition-all"
                    >
                        Login Sekarang
                    </a>
                </div>
            </div>
        </section>
    );

    const AlreadyResellerBanner = () => (
        <section className="max-w-4xl mx-auto px-6 pb-8">
            <div className="glass-panel rounded-xl p-8 text-center border-2 border-green-500/30">
                <div className="text-6xl mb-4">✅</div>
                <h3 className="text-2xl font-bold text-white mb-3">
                    Anda Sudah Menjadi Reseller
                </h3>
                <p className="text-gray-400 mb-6 max-w-lg mx-auto">
                    Akun Anda sudah memiliki status {current_user?.role}.
                    Anda dapat langsung menggunakan fitur H2H API.
                </p>
                <a
                    href="/id/dashboard"
                    className="inline-block px-6 py-3 bg-gradient-to-b from-blue-500 to-blue-600 text-white font-semibold rounded-lg hover:shadow-lg transition-all"
                >
                    Ke Dashboard
                </a>
            </div>
        </section>
    );

    const EligibilityErrorBanner = () => (
        <section className="max-w-4xl mx-auto px-6 pb-8">
            <div className="glass-panel rounded-xl p-8 border-2 border-red-500/30">
                <div className="flex gap-6 items-start">
                    <div className="text-5xl">❌</div>
                    <div className="flex-1">
                        <h3 className="text-2xl font-bold text-white mb-3">
                            Tidak Memenuhi Syarat
                        </h3>
                        <p className="text-gray-400 mb-6">
                            {pageErrors.eligibility}
                        </p>
                        <a
                            href="/id/dashboard"
                            className="inline-block px-6 py-3 bg-gradient-to-b from-blue-500 to-blue-600 text-white font-semibold rounded-lg hover:shadow-lg transition-all"
                        >
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </section>
    );

    const PendingApplicationBanner = () => (
        <section className="max-w-4xl mx-auto px-6 pb-8">
            <div className="glass-panel rounded-xl p-8 border-2 border-orange-500/30">
                <div className="flex gap-6 items-start">
                    <div className="text-5xl">⏳</div>
                    <div className="flex-1">
                        <h3 className="text-2xl font-bold text-white mb-3">
                            Pengajuan Sedang Dalam Review
                        </h3>
                        <p className="text-gray-400 mb-6">
                            Pengajuan reseller Anda telah diterima dan sedang dalam proses peninjauan oleh tim kami.
                            Kami akan menghubungi Anda melalui email dalam 1-3 hari kerja.
                        </p>

                        <div className="glass-panel rounded-lg p-4 mb-4">
                            <div className="flex justify-between items-center mb-2">
                                <span className="text-sm text-gray-400">Status:</span>
                                <span className="text-sm font-semibold text-orange-500 px-3 py-1 bg-orange-500/20 rounded-full">
                                    📋 Pending Review
                                </span>
                            </div>
                            {existing_application?.applied_at && (
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-gray-400">Tanggal Pengajuan:</span>
                                    <span className="text-sm text-white">
                                        {new Date(existing_application.applied_at).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric'
                                        })}
                                    </span>
                                </div>
                            )}
                        </div>

                        <div className="p-3 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                            <p className="text-sm text-gray-300">
                                💡 <strong>Info:</strong> Anda tidak dapat mengajukan ulang selama pengajuan masih dalam status review.
                                Harap menunggu hasil peninjauan dari tim kami.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );

    // If submission was successful, show success timeline
    if (submissionSuccess) {
        return (
            <>
                <Head>
                    <title>{`Pengajuan Berhasil - ${appName}`}</title>
                </Head>
                <SuccessTimeline />
            </>
        );
    }

    // Main render
    return (
        <>
            <Head>
                <title>{`Pendaftaran Reseller - ${appName}`}</title>
                <meta name="description" content="Bergabunglah dengan mitra resmi kami dan dapatkan akses eksklusif ke sistem H2H dengan harga kompetitif" />
                <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
                {captcha?.enabled && captcha?.site_key && (
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                )}
            </Head>

            <div className="min-h-screen bg-gradient-to-b from-gray-900 via-gray-900 to-black relative overflow-hidden">
                {/* Background decorative glows */}
                <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-[120px] pointer-events-none"></div>
                <div className="absolute bottom-1/4 right-1/4 w-64 h-64 bg-purple-500/10 rounded-full blur-[100px] pointer-events-none"></div>

                {/* Header */}
                <header className="fixed top-0 w-full z-50 bg-gray-900/80 backdrop-blur-md border-b border-white/5 shadow-sm">
                    <div className="flex justify-between items-center px-6 py-4 max-w-7xl mx-auto">
                        {normalizedLogo ? (
                            <img src={normalizedLogo} alt="Logo" className="h-8" />
                        ) : (
                            <div className="text-2xl font-bold text-blue-400">{appName}</div>
                        )}
                        <div className="flex gap-4 items-center">
                            <a className="text-gray-400 text-sm font-semibold hover:text-white transition-colors" href="/id/dashboard">
                                Beranda
                            </a>
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main className="flex-grow pt-24 pb-16 px-6 relative z-10">
                    {/* Account Info Section for authenticated users */}
                    {isAuthenticated && !isReseller && <AccountInfoSection />}

                    {/* Guest Banner - Show for guests */}
                    {!isAuthenticated && <GuestBanner />}

                    {/* Already Reseller Banner */}
                    {isAuthenticated && isReseller && <AlreadyResellerBanner />}

                    {/* Pending Application Banner */}
                    {isAuthenticated && isMember && hasPendingApplication && <PendingApplicationBanner />}

                    {/* Show eligibility error banner if there's an eligibility error */}
                    {isAuthenticated && isMember && !hasPendingApplication && pageErrors?.eligibility && <EligibilityErrorBanner />}

                    {/* Show multi-step form - visible to everyone but disabled for guests */}
                    {!isReseller && !hasPendingApplication && !pageErrors?.eligibility && (
                        <div className="w-full">
                            {/* Progress Bar */}
                            <ProgressBar currentStep={currentStep} />

                            {/* Step Content */}
                            {currentStep === 1 && (
                                <Step1BusinessInfo
                                    formData={data}
                                    onChange={handleFieldChange}
                                    onNext={handleNextStep}
                                    errors={errors}
                                    supportWhatsappUrl={support_whatsapp_url}
                                    disabled={!isAuthenticated}
                                />
                            )}

                            {currentStep === 2 && (
                                <Step2Documents
                                    formData={data}
                                    onChange={handleFieldChange}
                                    onSubmit={handleFinalSubmit}
                                    onBack={handleBack}
                                    errors={errors}
                                    captcha={captcha}
                                    processing={processing}
                                    disabled={!isAuthenticated}
                                />
                            )}
                        </div>
                    )}
                </main>
            </div>

            {/* Global Styles */}
            <style dangerouslySetInnerHTML={{
                __html: `
                .glass-panel {
                    background-color: rgba(22, 27, 34, 0.8);
                    backdrop-filter: blur(12px);
                    border: 1px solid rgba(255, 255, 255, 0.05);
                }
                
                .btn-primary {
                    background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
                    transition: all 0.2s ease-in-out;
                }
                
                .btn-primary:hover {
                    box-shadow: 0 0 25px rgba(59, 130, 246, 0.6);
                    transform: translateY(-1px);
                }
                
                .input-glass {
                    background-color: #0a0c10;
                    border: 1px solid #1e293b;
                    color: #e1e2ec;
                }
                
                .input-glass:focus {
                    border-color: #3b82f6;
                    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
                    outline: none;
                }
                
                .material-symbols-outlined {
                    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
                }
            ` }} />
        </>
    );
}
