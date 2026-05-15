<x-filament-panels::page>
    <style>
        .custom-dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; /* 2 bagian kiri, 1 bagian kanan */
            gap: 2rem; /* Jarak antar kolom */
        }


        .left-column {
            display: flex;
            flex-direction: column;
            gap: 2rem; /* Jarak vertikal antar widget kiri */
        }

        .right-column {
            display: flex;
            flex-direction: column;
            gap: 2rem; /* Jarak vertikal antar widget kanan */
        }

        /* Responsif untuk mobile */
        @media (max-width: 1024px) {
            .custom-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="custom-dashboard-grid" data-onboarding-target="dashboard-grid">
        <!-- Left Column (Stats + Product Table) -->
        <div class="left-column">
            <div data-onboarding-target="dashboard-stats">
                @livewire(\App\Filament\Admin\Widgets\UserStatsOverview::class)
            </div>
            <div data-onboarding-target="dashboard-revenue">
                @livewire(\App\Filament\Admin\Widgets\RevenueChart::class)
            </div>
            <div data-onboarding-target="dashboard-products">
                @livewire(\App\Filament\Admin\Widgets\ProductMetrics::class)
            </div>
        </div>

        <!-- Right Column (Leaderboard + Chart) -->
        <div class="right-column">
            <div data-onboarding-target="dashboard-activities">
                @livewire(\App\Filament\Admin\Widgets\RecentActivities::class)
            </div>
            <div>
                @livewire(\App\Filament\Admin\Widgets\ProfitAnalysis::class)
            </div>
            <div>
                @livewire(\App\Filament\Admin\Widgets\UserTierChart::class)
            </div>
        </div>
    </div>

    @include('filament.admin.onboarding.guide')
</x-filament-panels::page>
