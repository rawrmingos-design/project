@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        x-data="{ state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }} }"
        style="display:flex;flex-direction:column;gap:12px;"
    >
        <input
            type="hidden"
            {{
                $attributes
                    ->merge([
                        'id' => $getId(),
                        $wireModelAttribute => $statePath,
                    ], escape: false)
                    ->class(['fi-fo-hidden'])
            }}
        />

        @if (blank($assets))
            <div style="padding:18px;border:1px dashed rgba(148,163,184,.35);border-radius:14px;background:rgba(15,23,42,.25);font-size:13px;color:#94a3b8;">
                Belum ada asset valid yang cocok dengan filter saat ini.
            </div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">
                @foreach ($assets as $asset)
                    <button
                        type="button"
                        x-on:click="state = '{{ $asset['id'] }}'"
                        x-bind:style="String(state) === '{{ $asset['id'] }}'
                            ? 'display:flex;flex-direction:column;gap:8px;padding:10px;border-radius:14px;background:rgba(15,23,42,.45);border:2px solid rgba(59,130,246,.95);box-shadow:0 0 0 1px rgba(59,130,246,.2);text-align:left;cursor:pointer;'
                            : 'display:flex;flex-direction:column;gap:8px;padding:10px;border-radius:14px;background:rgba(15,23,42,.35);border:1px solid rgba(148,163,184,.2);text-align:left;cursor:pointer;'"
                    >
                        <img
                            src="{{ $asset['url'] }}"
                            alt="{{ $asset['alt'] }}"
                            style="width:100%;height:118px;object-fit:cover;border-radius:10px;"
                        />

                        <div style="display:flex;flex-direction:column;gap:4px;min-width:0;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                <span style="font-size:11px;color:#94a3b8;">{{ $asset['folder'] }}</span>
                                <span
                                    x-show="String(state) === '{{ $asset['id'] }}'"
                                    style="font-size:11px;color:#60a5fa;font-weight:600;"
                                >
                                    Dipilih
                                </span>
                            </div>

                            <span style="font-size:13px;color:#e2e8f0;font-weight:600;line-height:1.4;">
                                {{ $asset['name'] }}
                            </span>

                            <span style="font-size:11px;color:#94a3b8;line-height:1.35;word-break:break-word;">
                                {{ $asset['path'] }}
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</x-dynamic-component>
