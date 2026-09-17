<?php

namespace App\Services;

use App\Models\Pembelian;
use Illuminate\Database\Eloquent\Builder;

final class PublicInvoiceReferenceResolver
{
    public function resolve(?string $reference): ?Pembelian
    {
        $reference = trim((string) $reference);

        if ($reference === '') {
            return null;
        }

        return Pembelian::query()
            ->where(function (Builder $query) use ($reference): void {
                $query->where('order_id', $reference)
                    ->orWhere('display_order_id', $reference);
            })
            ->latest('id')
            ->first();
    }

    public function resolveOrderId(?string $reference): ?string
    {
        return $this->resolve($reference)?->order_id;
    }
}
