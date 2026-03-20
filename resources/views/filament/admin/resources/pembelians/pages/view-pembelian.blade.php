<x-filament-panels::page>
    <div class="space-y-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 overflow-hidden border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-xs font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">OI</span>
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Order Information</h3>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Order ID</label>
                            <div class="flex items-center gap-2">
                                <div class="fi-input-wrp flex flex-1 rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                    <input type="text" value="{{ $record->order_id }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 font-mono text-sm text-gray-950 outline-none dark:text-white" />
                                </div>
                                <button onclick="navigator.clipboard.writeText('{{ $record->order_id }}')" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Display Invoice</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="{{ $record->display_order_id }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 font-mono text-sm text-gray-950 outline-none dark:text-white" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            @php
                                $statusColors = [
                                    'Success' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                    'Pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
                                    'Processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
                                    'Failed' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                                    'Cancelled' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                                    'Refunded' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
                                ];
                                $statusClass = $statusColors[$record->status_display_label] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                {{ $record->status_display_label }}
                            </span>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="{{ $record->user->name ?? 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="{{ $record->username ?? 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Product/Service</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="{{ $record->layanan }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="Rp {{ number_format($record->harga, 0, ',', '.') }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm font-bold text-green-600 outline-none dark:text-green-400" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Current Provider</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="{{ $this->getCurrentProviderLabel() }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 overflow-hidden border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-info-50 text-xs font-semibold text-info-600 dark:bg-info-500/10 dark:text-info-400">GD</span>
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Game Details</h3>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Zone/Server ID</label>
                        <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                            <input type="text" value="{{ $record->zone ?? 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Game Nickname</label>
                        <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                            <input type="text" value="{{ $record->nickname ?? 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Transaction Type</label>
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                            {{ ucfirst($record->tipe_transaksi) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 overflow-hidden border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-warning-50 text-xs font-semibold text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">RC</span>
                <div>
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Reset Context</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Reset state stays read-only on the page body. Any allowed adjustments happen through header modal actions.</p>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Reset Status</label>
                        <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                            <input type="text" value="{{ $this->getResetStatusLabel() }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice Version</label>
                        <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                            <input type="text" value="{{ $record->invoice_version }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Active Attempt Reference</label>
                        <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                            <input type="text" value="{{ $record->active_attempt_reference ?? $record->display_order_id }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 font-mono text-sm text-gray-950 outline-none dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Reset Reason</label>
                        <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                            <input type="text" value="{{ $record->reset_reason ?: 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 overflow-hidden border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-success-50 text-xs font-semibold text-success-600 dark:bg-success-500/10 dark:text-success-400">TD</span>
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Transaction Details</h3>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Provider Order ID</label>
                            <div class="flex items-center gap-2">
                                <div class="fi-input-wrp flex flex-1 rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                    <input type="text" value="{{ $record->provider_order_id ?? 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 font-mono text-sm text-gray-950 outline-none dark:text-white" />
                                </div>
                                @if ($record->provider_order_id)
                                    <button onclick="navigator.clipboard.writeText('{{ $record->provider_order_id }}')" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                        Copy
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Voucher Code</label>
                            <div class="flex items-center gap-2">
                                <div class="fi-input-wrp flex flex-1 rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                    <input type="text" value="{{ $record->voucher ?? 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 font-mono text-sm text-gray-950 outline-none dark:text-white" />
                                </div>
                                @if ($record->voucher)
                                    <button onclick="navigator.clipboard.writeText('{{ $record->voucher }}')" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                        Copy
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Profit</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="Rp {{ number_format($record->profit, 0, ',', '.') }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm font-bold text-blue-600 outline-none dark:text-blue-400" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">IP Address</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="{{ $record->ip_address ?? 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 font-mono text-sm text-gray-950 outline-none dark:text-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 overflow-hidden border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">SI</span>
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">System Information</h3>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan / SN</label>
                        <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                            <input type="text" value="{{ $record->keterangan_sn ?? $record->voucher ?? 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">System Log</label>
                        <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                            <div class="min-h-[4rem] flex-1 p-3">
                                <pre class="whitespace-pre-wrap break-words text-xs font-mono text-gray-950 dark:text-white">{{ $record->log ?? 'N/A' }}</pre>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Created At</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="{{ $record->created_at ? $record->created_at->format('d M Y H:i:s') : 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</label>
                            <div class="fi-input-wrp flex rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 dark:bg-white/5 dark:ring-white/20">
                                <input type="text" value="{{ $record->updated_at ? $record->updated_at->format('d M Y H:i:s') : 'N/A' }}" readonly class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
