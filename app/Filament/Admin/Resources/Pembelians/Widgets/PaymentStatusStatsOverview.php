<?php

namespace App\Filament\Admin\Resources\Pembelians\Widgets;

use App\Models\Pembelian;
use App\Support\PaymentStatus;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PaymentStatusStatsOverview extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.admin.widgets.order-stats-overview';

    public string $paymentStatus = PaymentStatus::PENDING;
    public string $paymentPeriod = 'today';
    public ?string $paymentStartDate = null;
    public ?string $paymentEndDate = null;

    protected function getViewData(): array
    {
        $status = $this->activeStatus();
        $count = $this->countForStatus($status, $this->paymentPeriod, $this->paymentStartDate, $this->paymentEndDate);

        return [
            'heading' => 'Status Pembayaran',
            'periodOptions' => $this->periodOptions(),
            'cards' => [
                [
                    'label' => 'Pembayaran',
                    'value' => $this->formatNumber($count),
                    'description' => 'Payment gateway: ' . (PaymentStatus::switchOptions()[$status] ?? 'Pending'),
                    'icon' => PaymentStatus::icon($status),
                    'color' => PaymentStatus::badgeColor($status),
                    'statusProperty' => 'paymentStatus',
                    'status' => $status,
                    'statusOptions' => PaymentStatus::switchOptions(),
                    'periodProperty' => 'paymentPeriod',
                    'period' => $this->paymentPeriod,
                    'startDateProperty' => 'paymentStartDate',
                    'endDateProperty' => 'paymentEndDate',
                    'startDate' => $this->paymentStartDate,
                    'endDate' => $this->paymentEndDate,
                ],
            ],
        ];
    }

    private function activeStatus(): string
    {
        return array_key_exists($this->paymentStatus, PaymentStatus::switchOptions())
            ? $this->paymentStatus
            : PaymentStatus::PENDING;
    }

    private function countForStatus(string $status, string $period, ?string $startDate, ?string $endDate): int
    {
        return (int) PaymentStatus::applyPembelianQuery(
            $this->baseQuery($period, $startDate, $endDate),
            [$status],
        )->count();
    }

    private function baseQuery(string $period, ?string $startDate, ?string $endDate): Builder
    {
        $query = Pembelian::query()->whereNull('reseller_integration_id');

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

    private function formatNumber(int|float $number): string
    {
        return number_format((float) $number, 0, ',', '.');
    }
}
