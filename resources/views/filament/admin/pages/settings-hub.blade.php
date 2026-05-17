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
    <style>
        .settings-hub {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .settings-hub__intro {
            border: 1px solid rgba(71, 85, 105, .5);
            background: rgba(15, 23, 42, .72);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .06);
        }

        .settings-hub__title {
            margin: 0;
            font-size: 1.15rem;
            line-height: 1.4;
            font-weight: 700;
            color: #f8fafc;
        }

        .settings-hub__subtitle {
            margin-top: .45rem;
            font-size: .95rem;
            line-height: 1.55;
            color: rgba(226, 232, 240, .9);
            max-width: 74ch;
        }

        .settings-hub__grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: .9rem;
        }

        @media (min-width: 900px) {
            .settings-hub__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1440px) {
            .settings-hub__grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .settings-hub__card {
            display: block;
            border: 1px solid rgba(71, 85, 105, .48);
            background: rgba(15, 23, 42, .72);
            border-radius: 16px;
            padding: 1rem 1rem .95rem;
            transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease, background-color .18s ease;
            text-decoration: none;
        }

        .settings-hub__card:hover,
        .settings-hub__card:focus-visible {
            border-color: rgba(96, 165, 250, .55);
            background: rgba(15, 23, 42, .9);
            box-shadow: 0 10px 22px rgba(2, 6, 23, .4);
            transform: translateY(-1px);
        }

        .settings-hub__card:focus-visible {
            outline: 2px solid rgba(96, 165, 250, .4);
            outline-offset: 2px;
        }

        .settings-hub__card-head {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
        }

        .settings-hub__icon-wrap {
            width: 2rem;
            height: 2rem;
            min-width: 2rem;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(71, 85, 105, .6);
            background: rgba(30, 41, 59, .9);
            color: #60a5fa;
        }

        .settings-hub__icon {
            width: 1.05rem;
            height: 1.05rem;
        }

        .settings-hub__card-title {
            margin: 0;
            font-size: 1rem;
            line-height: 1.35;
            font-weight: 700;
            color: #f8fafc;
        }

        .settings-hub__card-desc {
            margin: .35rem 0 0;
            font-size: .875rem;
            line-height: 1.55;
            color: rgba(226, 232, 240, .9);
        }
    </style>

    <div class="settings-hub" data-onboarding-target="settings-hub">
        <div class="settings-hub__intro">
            <h2 class="settings-hub__title">Settings Hub</h2>
            <p class="settings-hub__subtitle">
                Pilih submenu di bawah untuk mengelola pengaturan berdasarkan domain kerja supaya lebih fokus dan rapi.
            </p>
        </div>

        <div class="settings-hub__grid" data-onboarding-target="settings-hub-cards">
            @foreach ($settingsMenus as $menu)
                <a
                    href="{{ $menu['url'] }}"
                    class="settings-hub__card"
                    data-onboarding-target="settings-hub-{{ \Illuminate\Support\Str::slug($menu['title']) }}"
                >
                    <div class="settings-hub__card-head">
                        <span class="settings-hub__icon-wrap" aria-hidden="true">
                            <x-filament::icon
                                :icon="$menu['icon']"
                                class="settings-hub__icon"
                            />
                        </span>
                        <div>
                            <h3 class="settings-hub__card-title">{{ $menu['title'] }}</h3>
                            <p class="settings-hub__card-desc">{{ $menu['description'] }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
