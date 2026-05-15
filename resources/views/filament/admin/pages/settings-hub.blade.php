@php
    $settingsMenus = [
        [
            'title' => 'General',
            'description' => 'Informasi website, popup homepage, live sales toast, dan CAPTCHA login admin.',
            'url' => \App\Filament\Admin\Pages\Settings\GeneralSettings::getUrl(),
            'icon' => 'heroicon-o-adjustments-horizontal',
        ],
        [
            'title' => 'Branding',
            'description' => 'Kelola logo, warna brand, seasonal theme, dan link sosial media.',
            'url' => \App\Filament\Admin\Pages\Settings\BrandingSettings::getUrl(),
            'icon' => 'heroicon-o-swatch',
        ],
        [
            'title' => 'SEO & Tracking',
            'description' => 'Atur Analytics, Pixel, GTM, robots, sitemap, dan validasi XML.',
            'url' => \App\Filament\Admin\Pages\Settings\SeoTrackingSettings::getUrl(),
            'icon' => 'heroicon-o-chart-bar-square',
        ],
        [
            'title' => 'Payment Gateways',
            'description' => 'Konfigurasi gateway pembayaran, deposit, akun e-wallet, dan rekening bank.',
            'url' => \App\Filament\Admin\Pages\Settings\PaymentGatewaysSettings::getUrl(),
            'icon' => 'heroicon-o-credit-card',
        ],
        [
            'title' => 'Providers & API',
            'description' => 'Simpan credential provider seperti Digiflazz, VIP Reseller, API Games, dan lainnya.',
            'url' => \App\Filament\Admin\Pages\Settings\ProvidersApiSettings::getUrl(),
            'icon' => 'heroicon-o-server-stack',
        ],
        [
            'title' => 'Notifications',
            'description' => 'Konfigurasi WhatsApp, SMTP email, test channel, dan aturan pengiriman invoice.',
            'url' => \App\Filament\Admin\Pages\Settings\NotificationsSettings::getUrl(),
            'icon' => 'heroicon-o-bell-alert',
        ],
        [
            'title' => 'Membership & Rewards',
            'description' => 'Atur markup tier, threshold membership, dan konfigurasi poin pengguna.',
            'url' => \App\Filament\Admin\Pages\Settings\MembershipRewardsSettings::getUrl(),
            'icon' => 'heroicon-o-star',
        ],
    ];
@endphp

<x-filament-panels::page>
    <div class="space-y-6" data-onboarding-target="settings-hub">
        <div class="rounded-2xl border border-gray-700/60 bg-gray-900/60 p-6">
            <h2 class="text-xl font-semibold text-white">Settings Hub</h2>
            <p class="mt-2 text-sm text-gray-300">
                Pilih submenu di bawah untuk mengelola pengaturan berdasarkan domain kerja supaya lebih fokus dan rapi.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-onboarding-target="settings-hub-cards">
            @foreach ($settingsMenus as $menu)
                <a
                    href="{{ $menu['url'] }}"
                    class="group rounded-2xl border border-gray-700/70 bg-gray-900/60 p-5 transition hover:border-primary-500/70 hover:bg-gray-900"
                    data-onboarding-target="settings-hub-{{ \Illuminate\Support\Str::slug($menu['title']) }}"
                >
                    <div class="flex items-start gap-3">
                        <x-filament::icon
                            :icon="$menu['icon']"
                            class="mt-0.5 h-5 w-5 text-primary-400 group-hover:text-primary-300"
                        />
                        <div>
                            <h3 class="text-base font-semibold text-white">{{ $menu['title'] }}</h3>
                            <p class="mt-1 text-sm text-gray-300">{{ $menu['description'] }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>

