<x-filament-widgets::widget>
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: .75rem;">
            <h2 style="margin: 0; font-size: 1rem; font-weight: 700; line-height: 1.5; color: rgb(248 250 252);">
                {{ $heading }}
            </h2>
            <span style="font-size: .75rem; font-weight: 600; color: rgb(148 163 184);">
                Filter tiap kartu independen
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            @foreach ($cards as $card)
                @php
                    $tone = match ($card['color']) {
                        'success' => ['bg' => 'rgba(16, 185, 129, .12)', 'fg' => 'rgb(52, 211, 153)', 'ring' => 'rgba(52, 211, 153, .22)'],
                        'danger' => ['bg' => 'rgba(239, 68, 68, .12)', 'fg' => 'rgb(248, 113, 113)', 'ring' => 'rgba(248, 113, 113, .22)'],
                        'warning' => ['bg' => 'rgba(245, 158, 11, .12)', 'fg' => 'rgb(251, 191, 36)', 'ring' => 'rgba(251, 191, 36, .22)'],
                        default => ['bg' => 'rgba(148, 163, 184, .12)', 'fg' => 'rgb(148, 163, 184)', 'ring' => 'rgba(148, 163, 184, .22)'],
                    };
                @endphp

                <div style="border-radius: 1rem; background: rgba(15, 23, 42, .72); border: 1px solid rgba(148, 163, 184, .14); box-shadow: 0 12px 30px rgba(2, 6, 23, .24); padding: 1.1rem; backdrop-filter: blur(10px);">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                        <div style="min-width: 0;">
                            <p style="margin: 0; font-size: .82rem; font-weight: 650; color: rgb(148, 163, 184);">
                                {{ $card['label'] }}
                            </p>
                            <p style="margin: .45rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 1.55rem; line-height: 1.15; font-weight: 800; letter-spacing: -.03em; color: rgb(248, 250, 252);">
                                {{ $card['value'] }}
                            </p>
                        </div>

                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 2.3rem; height: 2.3rem; border-radius: .85rem; background: {{ $tone['bg'] }}; color: {{ $tone['fg'] }}; border: 1px solid {{ $tone['ring'] }}; flex-shrink: 0;">
                            <x-filament::icon :icon="$card['icon']" style="width: 1.25rem; height: 1.25rem;" />
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: .55rem; margin-top: 1.2rem; min-width: 0;">
                        <p style="margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; color: rgb(148, 163, 184);">
                            {{ $card['description'] }}
                        </p>

                        <select
                            wire:model.live="{{ $card['periodProperty'] }}"
                            aria-label="Filter {{ $card['label'] }}"
                            style="display: block; width: 100%; max-width: 100%; box-sizing: border-box; border-radius: .65rem; border: 1px solid rgba(148, 163, 184, .22); background: rgba(2, 6, 23, .45); color: rgb(226, 232, 240); padding: .45rem 1.9rem .45rem .65rem; font-size: .75rem; font-weight: 650; outline: none;"
                        >
                            @foreach ($periodOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($card['period'] === 'custom')
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(116px, 1fr)); gap: .6rem; margin-top: .75rem; padding-top: .85rem; border-top: 1px solid rgba(148, 163, 184, .12); min-width: 0;">
                            <label style="display: flex; min-width: 0; flex-direction: column; gap: .35rem; font-size: .7rem; font-weight: 650; color: rgb(148, 163, 184);">
                                Dari
                                <input
                                    type="date"
                                    wire:model.live.debounce.500ms="{{ $card['startDateProperty'] }}"
                                    value="{{ $card['startDate'] }}"
                                    style="display: block; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; border-radius: .65rem; border: 1px solid rgba(148, 163, 184, .22); background: rgba(2, 6, 23, .45); color: rgb(226, 232, 240); padding: .42rem .5rem; font-size: .72rem; font-weight: 650; outline: none; color-scheme: dark;"
                                />
                            </label>

                            <label style="display: flex; min-width: 0; flex-direction: column; gap: .35rem; font-size: .7rem; font-weight: 650; color: rgb(148, 163, 184);">
                                Sampai
                                <input
                                    type="date"
                                    wire:model.live.debounce.500ms="{{ $card['endDateProperty'] }}"
                                    value="{{ $card['endDate'] }}"
                                    style="display: block; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; border-radius: .65rem; border: 1px solid rgba(148, 163, 184, .22); background: rgba(2, 6, 23, .45); color: rgb(226, 232, 240); padding: .42rem .5rem; font-size: .72rem; font-weight: 650; outline: none; color-scheme: dark;"
                                />
                            </label>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
