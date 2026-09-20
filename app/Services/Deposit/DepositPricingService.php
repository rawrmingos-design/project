<?php

namespace App\Services\Deposit;

use App\Http\Controllers\TriPayController;
use App\Models\Method;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for deposit pricing.
 *
 * Deposit has four different numbers that used to be computed in four different
 * places (React form, legacy Blade JS, DepositService, invoice page), which is how
 * the form ended up promising Rp 50.450 while Tripay charged Rp 51.554:
 *
 *   net_amount     : credited to the customer's balance (what they typed)
 *   admin_fee      : our own fee (method fee_percent + fix_fee)
 *   gateway_amount : what we ask the gateway to collect for us (net + admin fee)
 *   gateway_fee    : what the gateway charges the customer ON TOP of that amount
 *   total_amount   : what the customer really pays (gateway_amount + gateway_fee)
 *
 * Tripay adds its own customer fee on top of the requested amount (verified against
 * a real QRIS transaction: requested 50.450 -> amount 51.554, amount_received 50.450,
 * fee_customer 1.104), so total_amount is NOT net + admin_fee. The order flow already
 * handles this in GatewayPricingService; deposit paths must agree with it.
 */
class DepositPricingService
{
    public const MINIMUM_AMOUNT = 10000;

    private const GATEWAY_FEE_CACHE_SECONDS = 300;

    /**
     * @return array{net_amount: int, admin_fee: int, gateway_amount: int, gateway_fee: int, total_amount: int}
     */
    public function quote(int $netAmount, Method $method): array
    {
        $adminFee = $this->adminFee($netAmount, $method);
        $gatewayAmount = $netAmount + $adminFee;
        $gatewayFee = $this->gatewayCustomerFee($gatewayAmount, $method);

        return [
            'net_amount' => $netAmount,
            'admin_fee' => $adminFee,
            'gateway_amount' => $gatewayAmount,
            'gateway_fee' => $gatewayFee,
            'total_amount' => $gatewayAmount + $gatewayFee,
        ];
    }

    /**
     * Our own admin fee for the deposit amount, matching the historical formula
     * (`ceil(amount * percent) + fixed`) so existing expectations stay valid.
     */
    public function adminFee(int $netAmount, Method $method): int
    {
        $feePercent = (float) ($method->fee_percent ?? 0);
        $fixedFee = (float) ($method->fix_fee ?? 0);

        return (int) ceil($netAmount * ($feePercent / 100)) + (int) ceil($fixedFee);
    }

    /**
     * Fee the gateway charges the customer on top of the requested amount.
     *
     * Only Tripay does this; Duitku and Tokopay collect exactly what we ask for, so
     * their totals stay net + admin_fee. A failing fee API must never break the
     * deposit page, so it degrades to 0 (the pre-fix behaviour) instead of throwing.
     */
    public function gatewayCustomerFee(int $gatewayAmount, Method $method): int
    {
        if (! $this->isTripayMethod($method)) {
            return 0;
        }

        $code = (string) ($method->getRawOriginal('code') ?? $method->code ?? '');
        $cacheKey = 'deposit:gateway-fee:' . strtoupper($code) . ':' . $gatewayAmount;

        try {
            return (int) Cache::remember(
                $cacheKey,
                now()->addSeconds(self::GATEWAY_FEE_CACHE_SECONDS),
                fn (): int => max(0, (int) app(TriPayController::class)->customerFee($gatewayAmount, $code))
            );
        } catch (\Throwable) {
            // Fee API unavailable: fall back to "customer pays exactly what we ask for".
            return 0;
        }
    }

    /**
     * Reconcile the pricing we quoted with the amount the gateway actually asked for.
     *
     * The gateway response is authoritative: if Tripay collected more than we requested,
     * the customer's invoice must show that real amount, not our estimate.
     *
     * @param  array{net_amount: int, admin_fee: int, gateway_amount: int, gateway_fee: int, total_amount: int}  $quote
     * @return array{net_amount: int, admin_fee: int, gateway_amount: int, gateway_fee: int, total_amount: int}
     */
    public function reconcile(array $quote, ?int $gatewayChargedAmount): array
    {
        if ($gatewayChargedAmount === null || $gatewayChargedAmount < $quote['gateway_amount']) {
            return $quote;
        }

        return [
            ...$quote,
            'gateway_fee' => $gatewayChargedAmount - $quote['gateway_amount'],
            'total_amount' => $gatewayChargedAmount,
        ];
    }

    private function isTripayMethod(Method $method): bool
    {
        $gateway = strtolower(trim((string) ($method->getRawOriginal('payment') ?? $method->payment ?? '')));

        return $gateway === 'tripay';
    }
}
