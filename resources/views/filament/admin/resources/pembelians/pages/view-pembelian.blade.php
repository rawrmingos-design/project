<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Order Information -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-x-3">
                    <x-heroicon-o-shopping-cart class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                    Order Information
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <x-filament::input.wrapper>
                        <x-slot name="label">Order ID</x-slot>
                        <div class="flex items-center gap-2">
                            <x-filament::input
                                type="text"
                                value="{{ $record->order_id }}"
                                readonly
                                class="font-mono"
                            />
                            <x-filament::button
                                color="gray"
                                size="sm"
                                icon="heroicon-m-clipboard"
                                onclick="navigator.clipboard.writeText('{{ $record->order_id }}')"
                            >
                                Copy
                            </x-filament::button>
                        </div>
                    </x-filament::input.wrapper>
                    
                    <x-filament::input.wrapper>
                        <x-slot name="label">Status</x-slot>
                        @php
                            $statusColors = [
                                'Success' => 'success',
                                'Pending' => 'warning',
                                'Processing' => 'info',
                                'Failed' => 'danger',
                            ];
                            $statusColor = $statusColors[$record->status] ?? 'gray';
                        @endphp
                        <x-filament::badge :color="$statusColor">
                            {{ $record->status }}
                        </x-filament::badge>
                    </x-filament::input.wrapper>
                    
                    <x-filament::input.wrapper>
                        <x-slot name="label">Customer</x-slot>
                        <x-filament::input
                            type="text"
                            value="{{ $record->user->name ?? 'N/A' }}"
                            readonly
                        />
                    </x-filament::input.wrapper>
                </div>
                
                <div class="space-y-4">
                    <x-filament::input.wrapper>
                        <x-slot name="label">Username</x-slot>
                        <x-filament::input
                            type="text"
                            value="{{ $record->username ?? 'N/A' }}"
                            readonly
                        />
                    </x-filament::input.wrapper>
                    
                    <x-filament::input.wrapper>
                        <x-slot name="label">Product/Service</x-slot>
                        <x-filament::input
                            type="text"
                            value="{{ $record->layanan }}"
                            readonly
                        />
                    </x-filament::input.wrapper>
                    
                    <x-filament::input.wrapper>
                        <x-slot name="label">Amount</x-slot>
                        <x-filament::input
                            type="text"
                            value="Rp {{ number_format($record->harga, 0, ',', '.') }}"
                            readonly
                            class="font-bold text-green-600 dark:text-green-400"
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <!-- Game Details -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-x-3">
                    <x-heroicon-o-puzzle-piece class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                    Game Details
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-filament::input.wrapper>
                    <x-slot name="label">Zone/Server ID</x-slot>
                    <x-filament::input
                        type="text"
                        value="{{ $record->zone ?? 'N/A' }}"
                        readonly
                    />
                </x-filament::input.wrapper>
                
                <x-filament::input.wrapper>
                    <x-slot name="label">Game Nickname</x-slot>
                    <x-filament::input
                        type="text"
                        value="{{ $record->nickname ?? 'N/A' }}"
                        readonly
                    />
                </x-filament::input.wrapper>
                
                <x-filament::input.wrapper>
                    <x-slot name="label">Transaction Type</x-slot>
                    <x-filament::badge color="info">
                        {{ ucfirst($record->tipe_transaksi) }}
                    </x-filament::badge>
                </x-filament::input.wrapper>
            </div>
        </x-filament::section>

        <!-- Transaction Details -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-x-3">
                    <x-heroicon-o-credit-card class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                    Transaction Details
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <x-filament::input.wrapper>
                        <x-slot name="label">Provider Order ID</x-slot>
                        <div class="flex items-center gap-2">
                            <x-filament::input
                                type="text"
                                value="{{ $record->provider_order_id ?? 'N/A' }}"
                                readonly
                                class="font-mono"
                            />
                            @if($record->provider_order_id)
                                <x-filament::button
                                    color="gray"
                                    size="sm"
                                    icon="heroicon-m-clipboard"
                                    onclick="navigator.clipboard.writeText('{{ $record->provider_order_id }}')"
                                >
                                    Copy
                                </x-filament::button>
                            @endif
                        </div>
                    </x-filament::input.wrapper>
                    
                    <x-filament::input.wrapper>
                        <x-slot name="label">Voucher Code</x-slot>
                        <div class="flex items-center gap-2">
                            <x-filament::input
                                type="text"
                                value="{{ $record->voucher ?? 'N/A' }}"
                                readonly
                                class="font-mono"
                            />
                            @if($record->voucher)
                                <x-filament::button
                                    color="gray"
                                    size="sm"
                                    icon="heroicon-m-clipboard"
                                    onclick="navigator.clipboard.writeText('{{ $record->voucher }}')"
                                >
                                    Copy
                                </x-filament::button>
                            @endif
                        </div>
                    </x-filament::input.wrapper>
                </div>
                
                <div class="space-y-4">
                    <x-filament::input.wrapper>
                        <x-slot name="label">Profit</x-slot>
                        <x-filament::input
                            type="text"
                            value="Rp {{ number_format($record->profit, 0, ',', '.') }}"
                            readonly
                            class="font-bold text-blue-600 dark:text-blue-400"
                        />
                    </x-filament::input.wrapper>
                    
                    <x-filament::input.wrapper>
                        <x-slot name="label">IP Address</x-slot>
                        <x-filament::input
                            type="text"
                            value="{{ $record->ip_address ?? 'N/A' }}"
                            readonly
                            class="font-mono"
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <!-- System Information -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-x-3">
                    <x-heroicon-o-cog-6-tooth class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                    System Information
                </div>
            </x-slot>
            
            <div class="space-y-4">
                <x-filament::input.wrapper>
                    <x-slot name="label">Message</x-slot>
                    <x-filament::input
                        type="text"
                        value="{{ $record->message ?? 'N/A' }}"
                        readonly
                    />
                </x-filament::input.wrapper>
                
                <x-filament::input.wrapper>
                    <x-slot name="label">System Log</x-slot>
                    <x-filament::textarea
                        rows="3"
                        readonly
                        class="font-mono text-xs"
                    >{{ $record->log ?? 'N/A' }}</x-filament::textarea>
                </x-filament::input.wrapper>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-filament::input.wrapper>
                        <x-slot name="label">Created At</x-slot>
                        <x-filament::input
                            type="text"
                            value="{{ $record->created_at ? $record->created_at->format('d M Y H:i:s') : 'N/A' }}"
                            readonly
                        />
                    </x-filament::input.wrapper>
                    
                    <x-filament::input.wrapper>
                        <x-slot name="label">Last Updated</x-slot>
                        <x-filament::input
                            type="text"
                            value="{{ $record->updated_at ? $record->updated_at->format('d M Y H:i:s') : 'N/A' }}"
                            readonly
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
