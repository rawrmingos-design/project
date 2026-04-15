<x-filament-panels::page>
    <style>
        .custom-dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; /* 2 bagian kiri, 1 bagian kanan */
            gap: 2rem; /* Jarak antar kolom */
        }

        .dashboard-onboarding-toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        .dashboard-onboarding-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 0.875rem;
            border-radius: 999px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .dashboard-onboarding-trigger:hover {
            background: rgba(37, 99, 235, 0.14);
            border-color: rgba(37, 99, 235, 0.32);
            color: #1e40af;
        }

        .dark .dashboard-onboarding-trigger {
            color: #bfdbfe;
            background: rgba(59, 130, 246, 0.16);
            border-color: rgba(96, 165, 250, 0.28);
        }

        .dark .dashboard-onboarding-trigger:hover {
            color: #dbeafe;
            background: rgba(59, 130, 246, 0.22);
            border-color: rgba(147, 197, 253, 0.4);
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

    <div class="dashboard-onboarding-toolbar">
        <button type="button" class="dashboard-onboarding-trigger" data-onboarding-reopen>
            Lihat Panduan Lagi
        </button>
    </div>

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
