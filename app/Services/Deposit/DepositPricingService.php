<?php

namespace App\Services\Deposit;

use App\Http\Controllers\TriPayController;
use App\Models\Method;
use App\Services\Gateway\GatewayPricingService;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for deposit pricing.
 *
 * Deposit used to compute its total in four different places (React form, legacy Blade JS,
 * DepositService, invoice page), which is how the form could promise one number while the
 * gateway charged another.
 *
 * Follows the ORDER-flow convention (`GatewayPricingService` + the checkout gross-up):
 *
 *   net_amount     : credited to the customer's balance (what they typed)
 *   admin_fee      : our only visible fee (method fee_percent + fix_fee) → the "Biaya" row
 *   total_amount   : what the customer pays == net_amount + admin_fee
 *   gateway_amount : what we ask the gateway to collect. On Tripay this is LOWER than
 *                    total_amount, because the gateway adds its own customer fee on top
 *                    and we want the customer to pay exactly total_amount.
 *   gateway_fee    : the gateway's own customer fee, absorbed by the store. Kept for
 *                    reconciliation/accounting only — it is NOT a second charge on screen.
 *
 * Tripay adds its customer fee on top of the requested amount (verified against a real QRIS
 * transaction: requested 50.450 -> amount 51.554, amount_received 50.450, fee_customer
 * 1.104). Sending the displayed total straight through would therefore overcharge the
 * customer, so the request amount is reduced by the fee (see
 * `GatewayPricingService::resolveGatewayRequestAmount()`).
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

        // What the customer pays and what the invoice displays: nominal + our admin fee.
        $totalAmount = $netAmount + $adminFee;

        // Reverse the gateway's customer fee so the charge lands exactly on totalAmount.
        // Duitku/Tokopay collect exactly what we ask for, so they return totalAmount as-is
        // and their absorbed fee is 0.
        $gatewayAmount = app(GatewayPricingService::class)
            ->resolveGatewayRequestAmount($totalAmount, $method);

        $gatewayFee = max(0, $totalAmount - $gatewayAmount);

        return [
            'net_amount' => $netAmount,
            'admin_fee' => $adminFee,
            'gateway_amount' => $gatewayAmount,
            'gateway_fee' => $gatewayFee,
            'total_amount' => $totalAmount,
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
     * Fee the gateway charges the customer on top of the amount we ask it to collect.
     *
     * Only Tripay does this; Duitku and Tokopay collect exactly what we request, so their
     * totals stay net + admin_fee. A failing fee API must never break the deposit page, so
     * it degrades to 0 (the pre-fix behaviour) instead of throwing.
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
     * The gateway response is authoritative: if it collected a different total than we
     * expected, the invoice must show the real amount so the customer is never billed
     * something the invoice does not explain.
     *
     * @param  array{net_amount: int, admin_fee: int, gateway_amount: int, gateway_fee: int, total_amount: int}  $quote
     * @return array{net_amount: int, admin_fee: int, gateway_amount: int, gateway_fee: int, total_amount: int}
     */
    public function reconcile(array $quote, ?int $gatewayChargedAmount): array
    {
        if ($gatewayChargedAmount === null || $gatewayChargedAmount <= 0) {
            return $quote;
        }

        if ($gatewayChargedAmount === $quote['total_amount']) {
            return $quote;
        }

        return [
            ...$quote,
            'gateway_fee' => max(0, $gatewayChargedAmount - $quote['gateway_amount']),
            'total_amount' => $gatewayChargedAmount,
        ];
    }

    private function isTripayMethod(Method $method): bool
    {
        $gateway = strtolower(trim((string) ($method->getRawOriginal('payment') ?? $method->payment ?? '')));

        return $gateway === 'tripay';
    }
}
