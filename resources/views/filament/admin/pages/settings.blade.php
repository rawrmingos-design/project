<x-filament-panels::page>
    <div data-onboarding-target="settings-form">
        <form wire:submit="save">
            {{ $this->form }}

            <div class="fi-form-actions settings-form-actions" data-onboarding-target="settings-save">
                <x-filament::button
                    type="submit"
                    color="primary"
                >
                    Simpan Pengaturan
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
