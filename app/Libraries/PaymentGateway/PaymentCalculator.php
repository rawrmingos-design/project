<?php

namespace App\Libraries\PaymentGateway;

class PaymentCalculator
{
  private static $feeConfig = [
    'DANA' => ['percentage' => 0.025, 'fixed' => 0],
    'SHOPEEPAY' => ['percentage' => 0.025, 'fixed' => 0],
    'OVOPUSH' => ['percentage' => 0.025, 'fixed' => 0],
    'ASTRAPAY' => ['percentage' => 0.025, 'fixed' => 0],
    'LINKAJA' => ['percentage' => 0.03, 'fixed' => 0],
    'GOPAY' => ['percentage' => 0.03, 'fixed' => 0],
    'QRIS' => ['percentage' => 0.007, 'fixed' => 100],
    'QRISREALTIME' => ['percentage' => 0.017, 'fixed' => 0],
    'QRIS_CUSTOM' => ['percentage' => 0.027, 'fixed' => 0],
    'BCAVA' => ['percentage' => 0, 'fixed' => 4200],
    'BNIVA' => ['percentage' => 0, 'fixed' => 3500],
    'MANDIRIVA' => ['percentage' => 0, 'fixed' => 3500],
    'BSIVA' => ['percentage' => 0, 'fixed' => 3500],
    'BNCVA' => ['percentage' => 0, 'fixed' => 3000],
    'PERMATAVA' => ['percentage' => 0, 'fixed' => 2000],
    'CIMBVA' => ['percentage' => 0, 'fixed' => 2500],
    'DANAMONVA' => ['percentage' => 0, 'fixed' => 2500],
    'ALFAMART' => ['percentage' => 0, 'fixed' => 3000],
    'INDOMARET' => ['percentage' => 0, 'fixed' => 3000],
    'ALFAMIDI' => ['percentage' => 0, 'fixed' => 3000]
  ];

  public static function calculate($basePrice, $paymentMethod)
  {
    if (!isset(self::$feeConfig[$paymentMethod])) {
      return $basePrice;
    }

    $fee = self::$feeConfig[$paymentMethod];
    $percentageFee = $basePrice * ($fee['percentage'] ?? 0);
    $fixedFee = $fee['fixed'] ?? 0;

    return $basePrice + $percentageFee + $fixedFee;
  }

  public static function getFeeConfig($paymentMethod = null)
  {
    if ($paymentMethod) {
      return self::$feeConfig[$paymentMethod] ?? null;
    }
    return self::$feeConfig;
  }

  public static function validatePrice($calculatedPrice, $submittedPrice, $tolerance = 1)
  {
    return abs($calculatedPrice - $submittedPrice) <= $tolerance;
  }
}
