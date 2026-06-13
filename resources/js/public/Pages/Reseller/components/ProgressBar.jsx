export default function ProgressBar({ currentStep }) {
    return (
        <div className="mb-8 px-6">
            <div className="flex items-center justify-between relative max-w-md mx-auto">
                {/* Background line */}
                <div className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-0.5 bg-white/10 rounded-full z-0"></div>
                
                {/* Progress line (fills based on current step) */}
                <div 
                    className="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full z-0 transition-all duration-500"
                    style={{ width: currentStep === 1 ? '0%' : '100%' }}
                ></div>
                
                {/* Step 1 */}
                <div className="relative z-10 flex flex-col items-center gap-2">
                    <div className={`
                        w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm
                        transition-all duration-300
                        ${currentStep === 1 
                            ? 'bg-gradient-to-b from-blue-500 to-blue-600 text-white shadow-[0_0_15px_rgba(59,130,246,0.4)]' 
                            : 'bg-gradient-to-b from-blue-500 to-blue-600 text-white shadow-[0_0_10px_rgba(59,130,246,0.3)]'
                        }
                    `}>
                        {currentStep > 1 ? (
                            <span className="material-symbols-outlined text-lg font-bold">check</span>
                        ) : (
                            '1'
                        )}
                    </div>
                    <span className={`
                        text-xs font-semibold tracking-wide hidden sm:block transition-colors
                        ${currentStep >= 1 ? 'text-blue-400' : 'text-gray-500'}
                    `}>
                        Informasi Bisnis
                    </span>
                </div>
                
                {/* Step 2 */}
                <div className="relative z-10 flex flex-col items-center gap-2">
                    <div className={`
                        w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm
                        transition-all duration-300
                        ${currentStep === 2
                            ? 'bg-gray-800 border-2 border-blue-500 text-blue-400 shadow-[0_0_15px_rgba(59,130,246,0.3)]'
                            : 'bg-gray-800 border-2 border-gray-600 text-gray-400'
                        }
                    `}>
                        2
                    </div>
                    <span className={`
                        text-xs font-semibold tracking-wide hidden sm:block transition-colors
                        ${currentStep === 2 ? 'text-white' : 'text-gray-500'}
                    `}>
                        Upload Dokumen
                    </span>
                </div>
            </div>
        </div>
    );
}
