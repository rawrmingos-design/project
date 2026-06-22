<?php

namespace App\Filament\Admin\Resources\ResellerOrders\Widgets;

use App\Models\Pembelian;
use App\Support\PembelianStatus;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ResellerOrderStatsOverview extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.order-stats-overview';

    public string $profitPeriod = 'today';
    public ?string $profitStartDate = null;
    public ?string $profitEndDate = null;

    public string $successPeriod = 'today';
    public ?string $successStartDate = null;
    public ?string $successEndDate = null;

    public string $failedPeriod = 'today';
    public ?string $failedStartDate = null;
    public ?string $failedEndDate = null;

    public string $pendingPeriod = 'today';
    public ?string $pendingStartDate = null;
    public ?string $pendingEndDate = null;

    protected function getViewData(): array
    {
        return [
            'heading' => 'Ringkasan Reseller Orders',
            'periodOptions' => $this->periodOptions(),
            'cards' => [
                [
                    'label' => 'Profit',
                    'value' => $this->formatRupiah($this->sumProfit($this->profitPeriod, $this->profitStartDate, $this->profitEndDate)),
                    'description' => 'Reseller order sukses',
                    'icon' => 'heroicon-m-banknotes',
                    'color' => 'success',
                    'periodProperty' => 'profitPeriod',
                    'period' => $this->profitPeriod,
                    'startDateProperty' => 'profitStartDate',
                    'endDateProperty' => 'profitEndDate',
                    'startDate' => $this->profitStartDate,
                    'endDate' => $this->profitEndDate,
                ],
                [
                    'label' => 'Order Sukses',
                    'value' => $this->formatNumber($this->countByStatus(PembelianStatus::successLabels(), $this->successPeriod, $this->successStartDate, $this->successEndDate)),
                    'description' => 'Status Success / Sukses',
                    'icon' => 'heroicon-m-check-circle',
                    'color' => 'success',
                    'periodProperty' => 'successPeriod',
                    'period' => $this->successPeriod,
                    'startDateProperty' => 'successStartDate',
                    'endDateProperty' => 'successEndDate',
                    'startDate' => $this->successStartDate,
                    'endDate' => $this->successEndDate,
                ],
                [
                    'label' => 'Order Gagal',
                    'value' => $this->formatNumber($failedCount = $this->countByStatus(PembelianStatus::failedLabels(), $this->failedPeriod, $this->failedStartDate, $this->failedEndDate)),
                    'description' => 'Failed / Gagal / Batal',
                    'icon' => 'heroicon-m-x-circle',
                    'color' => $failedCount > 0 ? 'danger' : 'gray',
                    'periodProperty' => 'failedPeriod',
                    'period' => $this->failedPeriod,
                    'startDateProperty' => 'failedStartDate',
                    'endDateProperty' => 'failedEndDate',
                    'startDate' => $this->failedStartDate,
                    'endDate' => $this->failedEndDate,
                ],
                [
                    'label' => 'Order Pending',
                    'value' => $this->formatNumber($pendingCount = $this->countByStatus(PembelianStatus::pendingLabels(), $this->pendingPeriod, $this->pendingStartDate, $this->pendingEndDate)),
                    'description' => 'Pending / Proses / Processing',
                    'icon' => 'heroicon-m-clock',
                    'color' => $pendingCount > 0 ? 'warning' : 'gray',
                    'periodProperty' => 'pendingPeriod',
                    'period' => $this->pendingPeriod,
                    'startDateProperty' => 'pendingStartDate',
                    'endDateProperty' => 'pendingEndDate',
                    'startDate' => $this->pendingStartDate,
                    'endDate' => $this->pendingEndDate,
                ],
            ],
        ];
    }

    private function sumProfit(string $period, ?string $startDate, ?string $endDate): int|float
    {
        return (clone $this->baseQuery($period, $startDate, $endDate))
            ->whereIn('status', PembelianStatus::successLabels())
            ->sum('profit');
    }

    private function countByStatus(array $statuses, string $period, ?string $startDate, ?string $endDate): int
    {
        return (clone $this->baseQuery($period, $startDate, $endDate))
            ->whereIn('status', $statuses)
            ->count();
    }

    private function baseQuery(string $period, ?string $startDate, ?string $endDate): Builder
    {
        $query = Pembelian::query()->whereNotNull('reseller_integration_id');

        if ($range = $this->dateRange($period, $startDate, $endDate)) {
            $query->whereBetween('created_at', $range);
        }

        return $query;
    }

    private function dateRange(string $period, ?string $startDate, ?string $endDate): ?array
    {
        if ($period === 'custom') {
            return $this->customDateRange($startDate, $endDate);
        }

        return match ($period) {
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'last_7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfDay()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'last_3_months' => [now()->subMonthsNoOverflow(3)->startOfDay(), now()->endOfDay()],
            'all_time' => null,
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    private function customDateRange(?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function periodOptions(): array
    {
        return [
            'today' => 'Hari ini',
            'yesterday' => 'Kemarin',
            'last_7_days' => '7 hari terakhir',
            'this_month' => 'Bulan ini',
            'last_month' => 'Bulan lalu',
            'last_3_months' => '3 bulan terakhir',
            'all_time' => 'Semua waktu',
            'custom' => 'Custom range',
        ];
    }

    private function formatRupiah(int|float|string|null $amount): string
    {
        return 'Rp ' . number_format((float) ($amount ?? 0), 0, ',', '.');
    }

    private function formatNumber(int|float $number): string
    {
        return number_format((float) $number, 0, ',', '.');
    }
}
