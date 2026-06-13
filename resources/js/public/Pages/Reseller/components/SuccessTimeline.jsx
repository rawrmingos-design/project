export default function SuccessTimeline() {
    return (
        <div className="min-h-screen bg-gradient-to-b from-gray-900 via-gray-900 to-black flex flex-col items-center justify-center relative overflow-hidden">
            {/* Ambient Glow */}
            <div className="absolute top-0 w-full h-full bg-gradient-to-b from-blue-500/10 via-transparent to-transparent pointer-events-none"></div>
            
            <main className="w-full max-w-2xl px-6 py-16 relative z-10 flex flex-col items-center">
                {/* Success Icon */}
                <div className="w-24 h-24 rounded-full glass-panel flex items-center justify-center mb-8 border-2 border-green-500/30 relative">
                    <div className="absolute inset-0 rounded-full bg-green-500/10 animate-pulse"></div>
                    <span className="material-symbols-outlined text-green-400 text-5xl" style={{ fontVariationSettings: "'FILL' 1" }}>
                        check_circle
                    </span>
                </div>

                {/* Headline */}
                <h1 className="text-4xl md:text-5xl font-bold text-center mb-4 bg-clip-text text-transparent bg-gradient-to-r from-white to-gray-400">
                    Aplikasi Berhasil Dikirim!
                </h1>
                
                <p className="text-lg text-gray-400 text-center mb-12 max-w-lg">
                    Terima kasih telah mendaftar sebagai Reseller VoucherPro. Tim kami sedang meninjau informasi Anda.
                </p>

                {/* Status Card */}
                <div className="w-full glass-panel rounded-xl p-6 mb-12 flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
                    <div className="absolute left-0 top-0 bottom-0 w-1 bg-orange-500"></div>
                    
                    <div className="flex items-start gap-4 w-full md:w-auto">
                        <span className="material-symbols-outlined text-orange-500 mt-1">hourglass_top</span>
                        <div>
                            <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                                Status Aplikasi
                            </h3>
                            <p className="text-2xl font-semibold text-orange-500">Menunggu Review</p>
                        </div>
                    </div>
                    
                    <div className="flex flex-col gap-3 w-full md:w-auto border-t md:border-t-0 md:border-l border-white/10 pt-4 md:pt-0 md:pl-6">
                        <div className="flex items-center gap-2">
                            <span className="material-symbols-outlined text-gray-400 text-base">calendar_today</span>
                            <span className="text-sm text-gray-400">Estimasi: 1-3 hari kerja</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="material-symbols-outlined text-gray-400 text-base">mail</span>
                            <span className="text-sm text-gray-400">Kami akan mengirim notifikasi via email</span>
                        </div>
                    </div>
                </div>

                {/* Timeline Section */}
                <div className="w-full mb-12">
                    <h2 className="text-2xl font-semibold mb-6 border-b border-white/10 pb-3">
                        Apa yang terjadi selanjutnya?
                    </h2>
                    
                    <div className="space-y-6 relative before:absolute before:left-5 before:top-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-white/10 before:to-transparent">
                        {/* Step 1 - Active */}
                        <div className="relative flex items-center gap-0 group">
                            <div className="flex items-center justify-center w-10 h-10 rounded-full border-2 border-blue-500 bg-gray-900 shadow-lg shrink-0 z-10">
                                <span className="text-sm font-semibold text-blue-400">1</span>
                            </div>
                            <div className="ml-6 glass-panel p-4 rounded-lg flex-1">
                                <h4 className="text-xs font-semibold text-blue-400 mb-1 uppercase tracking-wide">
                                    Review Internal
                                </h4>
                                <p className="text-sm text-gray-400">
                                    Tim kepatuhan kami meninjau dokumen pendaftaran Anda untuk memastikan kesesuaian persyaratan.
                                </p>
                            </div>
                        </div>

                        {/* Step 2 - Pending */}
                        <div className="relative flex items-center gap-0 group opacity-60">
                            <div className="flex items-center justify-center w-10 h-10 rounded-full border border-white/20 bg-gray-900 shadow-lg shrink-0 z-10">
                                <span className="text-sm font-semibold text-gray-400">2</span>
                            </div>
                            <div className="ml-6 p-4 rounded-lg border border-transparent flex-1">
                                <h4 className="text-xs font-semibold text-white mb-1 uppercase tracking-wide">
                                    Verifikasi Akun
                                </h4>
                                <p className="text-sm text-gray-400">
                                    Anda akan menerima email konfirmasi untuk memverifikasi alamat email dan mengatur kata sandi.
                                </p>
                            </div>
                        </div>

                        {/* Step 3 - Pending */}
                        <div className="relative flex items-center gap-0 group opacity-60">
                            <div className="flex items-center justify-center w-10 h-10 rounded-full border border-white/20 bg-gray-900 shadow-lg shrink-0 z-10">
                                <span className="text-sm font-semibold text-gray-400">3</span>
                            </div>
                            <div className="ml-6 p-4 rounded-lg border border-transparent flex-1">
                                <h4 className="text-xs font-semibold text-white mb-1 uppercase tracking-wide">
                                    Upgrade & API Key
                                </h4>
                                <p className="text-sm text-gray-400">
                                    Status akun Anda di-upgrade menjadi Reseller dan Anda mendapatkan akses ke API Key transaksi.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Action Button */}
                <a 
                    href="/id/dashboard"
                    className="px-8 py-3 bg-gradient-to-b from-blue-500 to-blue-600 text-white font-semibold rounded-lg shadow-[0_0_20px_rgba(59,130,246,0.3)] hover:shadow-[0_0_30px_rgba(59,130,246,0.6)] transition-all flex items-center gap-2 hover:-translate-y-0.5"
                >
                    Kembali ke Dashboard
                    <span className="material-symbols-outlined text-lg">arrow_forward</span>
                </a>
            </main>
        </div>
    );
}
