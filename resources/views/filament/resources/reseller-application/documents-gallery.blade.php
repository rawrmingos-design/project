<style>
    .doc-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .doc-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
    }
    
    .dark .doc-card {
        background: #1f2937;
        border-color: #374151;
    }
    
    .doc-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.75rem;
    }
    
    .dark .doc-title {
        color: #ffffff;
    }
    
    .doc-info {
        margin-bottom: 1rem;
    }
    
    .doc-info-row {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .dark .doc-info-row {
        color: #9ca3af;
    }
    
    .doc-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .doc-btn {
        flex: 1;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-align: center;
        text-decoration: none;
        border-radius: 0.375rem;
        transition: all 0.15s;
        display: inline-block;
    }
    
    .doc-btn-primary {
        background: #3b82f6;
        color: #ffffff;
        border: 1px solid #3b82f6;
    }
    
    .doc-btn-primary:hover {
        background: #2563eb;
        border-color: #2563eb;
    }
    
    .doc-btn-secondary {
        background: #ffffff;
        color: #374151;
        border: 1px solid #d1d5db;
    }
    
    .dark .doc-btn-secondary {
        background: #1f2937;
        color: #d1d5db;
        border-color: #4b5563;
    }
    
    .doc-btn-secondary:hover {
        background: #f9fafb;
    }
    
    .dark .doc-btn-secondary:hover {
        background: #374151;
    }
    
    .doc-empty {
        text-align: center;
        padding: 2rem 0;
        color: #9ca3af;
    }
    
    .doc-empty-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .doc-empty-text {
        font-size: 0.75rem;
    }
</style>

@php
    $documents = $getRecord()->user->resellerDocuments ?? collect();
    
    $documentTypes = [
        'identity' => 'Identity Document (KTP)',
        'selfie' => 'Selfie with ID',
        'business_proof' => 'Business Proof',
    ];
@endphp

<div class="doc-grid">
    @foreach($documentTypes as $type => $label)
        @php
            $document = $documents->firstWhere('document_type', $type);
        @endphp
        
        <div class="doc-card">
            <div class="doc-title">{{ $label }}</div>
            
            @if($document)
                <div class="doc-info">
                    <div class="doc-info-row" title="{{ $document->file_name }}">
                        📄 {{ $document->file_name }}
                    </div>
                    <div class="doc-info-row">
                        📊 {{ $document->file_size ? round($document->file_size / 1024, 1) . ' KB' : 'N/A' }}
                    </div>
                    <div class="doc-info-row">
                        📅 {{ $document->created_at?->format('d M Y, H:i') }}
                    </div>
                    <div class="doc-info-row">
                        @php
                            $statusConfig = match($document->status ?? 'pending') {
                                'approved' => ['label' => 'Approved', 'color' => 'success'],
                                'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
                                default => ['label' => 'Pending', 'color' => 'warning'],
                            };
                        @endphp
                        <x-filament::badge :color="$statusConfig['color']" size="xs">
                            {{ $statusConfig['label'] }}
                        </x-filament::badge>
                    </div>
                </div>
                
                <div class="doc-actions">
                    <a href="{{ $document->file_url }}" target="_blank" class="doc-btn doc-btn-primary">
                        View
                    </a>
                    <a href="{{ $document->file_url }}" download class="doc-btn doc-btn-secondary">
                        Download
                    </a>
                </div>
            @else
                <div class="doc-empty">
                    <div class="doc-empty-icon">📄</div>
                    <div class="doc-empty-text">No document uploaded</div>
                </div>
            @endif
        </div>
    @endforeach
</div>
