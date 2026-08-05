<?php

namespace App\Console\Commands;

use App\Services\Payments\DuitkuReconciliationService;
use Illuminate\Console\Command;

class ReconcileDuitkuPayment extends Command
{
    protected $signature = 'duitku:reconcile {identifier : Local order ID or Duitku merchant order ID} {--merchant-order-id : Treat identifier as Duitku merchant order ID}';

    protected $description = 'Reconcile a Duitku payment against the transaction status API';

    public function handle(DuitkuReconciliationService $reconciliationService): int
    {
        try {
            $identifier = trim((string) $this->argument('identifier'));
            $result = $this->option('merchant-order-id')
                ? $reconciliationService->reconcileByMerchantOrderId($identifier)
                : $reconciliationService->reconcileByOrderId($identifier);


            return in_array($result['decision'] ?? null, ['paid', 'duplicate', 'pending', 'throttled'], true)
                ? self::SUCCESS
                : self::FAILURE;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
