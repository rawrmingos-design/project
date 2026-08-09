import { useState } from 'react';

export default function FileUploadZone({ 
    label, 
    helper, 
    icon, 
    accept, 
    maxSize = 5, // MB
    file,
    preview,
    error,
    onChange,
    disabled = false
}) {
    const [isDragging, setIsDragging] = useState(false);

    const handleDragOver = (e) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = (e) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);
        
        const droppedFile = e.dataTransfer.files[0];
        if (droppedFile) {
            validateAndSetFile(droppedFile);
        }
    };

    const handleFileChange = (e) => {
        const selectedFile = e.target.files[0];
        if (selectedFile) {
            validateAndSetFile(selectedFile);
        }
    };

    const validateAndSetFile = (file) => {
        // Check file size
        const fileSizeMB = file.size / (1024 * 1024);
        if (fileSizeMB > maxSize) {
            alert(`Ukuran file terlalu besar. Maksimal ${maxSize}MB.`);
            return;
        }
        
        onChange(file);
    };

    const handleRemove = () => {
        onChange(null);
    };

    return (
        <div className="flex flex-col gap-2">
            <label className="text-xs font-semibold text-white tracking-wide uppercase">
                {label}
            </label>
            
            {!file ? (
                <div
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    className={`
                        relative group border-2 border-dashed rounded-lg p-6 
                        flex flex-col items-center justify-center text-center
                        transition-all duration-300
                        ${disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
                        ${isDragging 
                            ? 'border-blue-500 bg-gray-800/80 shadow-[0_0_15px_rgba(59,130,246,0.2)]' 
                            : 'border-gray-600 bg-gray-900/50 hover:border-blue-500 hover:bg-gray-800/80 hover:shadow-[0_0_15px_rgba(59,130,246,0.1)]'
                        }
                    `}
                >
                    <input
                        type="file"
                        accept={accept}
                        onChange={handleFileChange}
                        disabled={disabled}
                        className={`absolute inset-0 w-full h-full opacity-0 ${disabled ? 'cursor-not-allowed' : 'cursor-pointer'}`}
                    />
                    
                    <div className="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center mb-3 group-hover:text-blue-400 transition-colors">
                        <span className="material-symbols-outlined text-2xl">{icon}</span>
                    </div>
                    
                    <p className="text-sm text-white mb-1">
                        <span className="text-blue-400 font-semibold">Klik untuk upload</span> atau drag and drop
                    </p>
                    
                    {helper && (
                        <p className="text-sm text-gray-400 mb-1">{helper}</p>
                    )}
                    
                    <p className="text-xs text-gray-500 opacity-70">
                        {accept.split(',').map(ext => ext.trim().toUpperCase()).join(', ')} (Max {maxSize}MB)
                    </p>
                </div>
            ) : (
                <div className="relative border-2 border-green-500/30 bg-gray-900/50 rounded-lg p-4 flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center flex-shrink-0">
                        <span className="material-symbols-outlined text-green-400">check_circle</span>
                    </div>
                    
                    <div className="flex-1 min-w-0">
                        <p className="text-sm text-white font-medium truncate">{file.name}</p>
                        <p className="text-xs text-gray-400">{(file.size / (1024 * 1024)).toFixed(2)} MB</p>
                    </div>
                    
                    <button
                        type="button"
                        onClick={handleRemove}
                        disabled={disabled}
                        className="flex-shrink-0 w-8 h-8 rounded-full bg-red-500/20 hover:bg-red-500/30 flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Hapus file"
                    >
                        <span className="material-symbols-outlined text-red-400 text-lg">close</span>
                    </button>
                </div>
            )}
            
            {error && (
                <p className="text-xs text-red-400">{error}</p>
            )}
        </div>
    );
}
