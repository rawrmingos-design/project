<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Order Information -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Order Information</h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Order ID</label>
                        <div class="mt-1 flex items-center">
                            <span class="text-sm text-gray-900 dark:text-white font-mono">{{ $record->order_id }}</span>
                            <button onclick="navigator.clipboard.writeText('{{ $record->order_id }}')" class="ml-2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>
                                    <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <div class="mt-1">
                            @php
                                $statusColors = [
                                    'Success' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                    'Pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
                                    'Processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
                                    'Failed' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                                ];
                                $statusColor = $statusColors[$record->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                {{ $record->status }}
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $record->user->name ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $record->username ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product/Service</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $record->layanan }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                        <div class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($record->harga, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Details -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Game Details</h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Zone/Server ID</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $record->zone ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Game Nickname</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $record->nickname ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Transaction Type</label>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                {{ ucfirst($record->tipe_transaksi) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Transaction Details</h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provider Order ID</label>
                        <div class="mt-1 flex items-center">
                            <span class="text-sm text-gray-900 dark:text-white font-mono">{{ $record->provider_order_id ?? 'N/A' }}</span>
                            @if($record->provider_order_id)
                                <button onclick="navigator.clipboard.writeText('{{ $record->provider_order_id }}')" class="ml-2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>
                                        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profit</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            Rp {{ number_format($record->profit, 0, ',', '.') }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Voucher Code</label>
                        <div class="mt-1 flex items-center">
                            <span class="text-sm text-gray-900 dark:text-white font-mono">{{ $record->voucher ?? 'N/A' }}</span>
                            @if($record->voucher)
                                <button onclick="navigator.clipboard.writeText('{{ $record->voucher }}')" class="ml-2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>
                                        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">IP Address</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $record->ip_address ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">System Information</h3>
            </div>
            <div class="px-6 py-4">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $record->message ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">System Log</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900 p-3 rounded-md font-mono">
                            {{ $record->log ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Created At</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $record->created_at ? $record->created_at->format('d M Y H:i:s') : 'N/A' }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $record->updated_at ? $record->updated_at->format('d M Y H:i:s') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
