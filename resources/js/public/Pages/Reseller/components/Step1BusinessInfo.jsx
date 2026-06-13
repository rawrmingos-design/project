import { useState } from 'react';

export default function Step1BusinessInfo({ formData, onChange, onNext, errors = {}, supportWhatsappUrl, disabled = false }) {
    const [charCount, setCharCount] = useState(formData.application_reason?.length || 0);

    const handleChange = (field, value) => {
        onChange(field, value);
        
        // Update character count for textarea
        if (field === 'application_reason') {
            setCharCount(value.length);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onNext();
    };

    return (
        <div className="w-full max-w-2xl mx-auto relative z-10">
            {/* Header */}
            <div className="text-center mb-8">
                <h1 className="text-3xl md:text-4xl font-bold text-white mb-2 tracking-tight">
                    Daftar Sebagai Reseller
                </h1>
                <p className="text-gray-400">
                    Dapatkan akses API H2H dan harga khusus reseller!
                </p>
            </div>

            {/* Form Card */}
            <div className="glass-panel rounded-xl p-6 md:p-8 shadow-lg relative">
                {/* Subtle glow behind card */}
                <div className="absolute inset-0 bg-blue-500/5 rounded-xl blur-xl pointer-events-none -z-10"></div>
                
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Nama Toko */}
                    <div>
                        <label 
                            className="block text-xs font-semibold text-white mb-2 tracking-wide uppercase"
                            htmlFor="business_name"
                        >
                            Nama Toko/Bisnis
                        </label>
                        <input
                            id="business_name"
                            name="business_name"
                            type="text"
                            value={formData.business_name || ''}
                            onChange={(e) => handleChange('business_name', e.target.value)}
                            placeholder="Masukkan nama bisnis Anda"
                            required
                            disabled={disabled}
                            className={`w-full input-glass rounded-md px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                        />
                        {errors.business_name && (
                            <p className="mt-1 text-xs text-red-400">{errors.business_name}</p>
                        )}
                    </div>

                    {/* Link Platform */}
                    <div>
                        <label 
                            className="block text-xs font-semibold text-white mb-2 tracking-wide uppercase"
                            htmlFor="business_url"
                        >
                            Link Platform
                        </label>
                        <div className="relative">
                            <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 pointer-events-none">
                                <span className="material-symbols-outlined text-xl">link</span>
                            </span>
                            <input
                                id="business_url"
                                name="business_url"
                                type="url"
                                value={formData.business_url || ''}
                                onChange={(e) => handleChange('business_url', e.target.value)}
                                placeholder="https://"
                                disabled={disabled}
                                className={`w-full input-glass rounded-md pl-12 pr-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                            />
                        </div>
                        <p className="mt-2 text-xs text-gray-400">
                            Contoh: Link toko Shopee, Instagram, atau website pribadi.
                        </p>
                        {errors.business_url && (
                            <p className="mt-1 text-xs text-red-400">{errors.business_url}</p>
                        )}
                    </div>

                    {/* Estimasi Transaksi */}
                    <div>
                        <label 
                            className="block text-xs font-semibold text-white mb-2 tracking-wide uppercase"
                            htmlFor="estimated_monthly_transactions"
                        >
                            Estimasi Transaksi per Bulan
                        </label>
                        <div className="relative">
                            <input
                                id="estimated_monthly_transactions"
                                name="estimated_monthly_transactions"
                                type="number"
                                min="0"
                                value={formData.estimated_monthly_transactions || ''}
                                onChange={(e) => handleChange('estimated_monthly_transactions', e.target.value)}
                                placeholder="0"
                                disabled={disabled}
                                className={`w-full input-glass rounded-md px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                            />
                            <span className="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 pointer-events-none text-sm">
                                Transaksi
                            </span>
                        </div>
                        {errors.estimated_monthly_transactions && (
                            <p className="mt-1 text-xs text-red-400">{errors.estimated_monthly_transactions}</p>
                        )}
                    </div>

                    {/* Alasan Mendaftar */}
                    <div>
                        <div className="flex justify-between items-end mb-2">
                            <label 
                                className="block text-xs font-semibold text-white tracking-wide uppercase"
                                htmlFor="application_reason"
                            >
                                Alasan Mendaftar
                            </label>
                            <span className={`text-xs ${charCount >= 500 ? 'text-red-400' : 'text-gray-400'}`}>
                                {charCount}/500
                            </span>
                        </div>
                        <textarea
                            id="application_reason"
                            name="application_reason"
                            value={formData.application_reason || ''}
                            onChange={(e) => handleChange('application_reason', e.target.value)}
                            placeholder="Ceritakan singkat tentang bisnis Anda dan alasan ingin bergabung..."
                            maxLength="500"
                            rows="4"
                            disabled={disabled}
                            className={`w-full input-glass rounded-md px-4 py-3 text-white placeholder-gray-500 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                        />
                        {errors.application_reason && (
                            <p className="mt-1 text-xs text-red-400">{errors.application_reason}</p>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="pt-4 flex flex-col sm:flex-row gap-4 justify-end items-center border-t border-white/5">
                        <button
                            type="button"
                            className="w-full sm:w-auto px-6 py-3 rounded-md text-sm font-semibold text-gray-400 hover:text-white transition-colors bg-transparent border border-gray-600 hover:bg-gray-800"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={disabled}
                            className={`w-full sm:w-auto px-6 py-3 rounded-md text-sm font-semibold text-white btn-primary flex items-center justify-center gap-2 ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                        >
                            Lanjut ke Upload Dokumen
                            <span className="material-symbols-outlined text-lg">arrow_forward</span>
                        </button>
                    </div>
                </form>
            </div>

            {/* Support Text */}
            <div className="mt-6 text-center">
                <p className="text-sm text-gray-400">
                    Butuh bantuan? <a href={supportWhatsappUrl || '#'} target="_blank" rel="noopener noreferrer" className="text-blue-400 hover:underline">Hubungi Tim Support</a>
                </p>
            </div>
        </div>
    );
}
