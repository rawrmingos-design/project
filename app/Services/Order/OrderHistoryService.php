<?php

namespace App\Services\Order;

use App\Models\Pembelian;
use App\Models\User;
use App\Support\PembelianStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OrderHistoryService
{
    public const TELEGRAM_LIMIT = 5;

    public const WHATSAPP_LIMIT = 15;

    public function __construct(
        private readonly OrderHistoryCursorCodec $cursors,
    ) {}

    /**
     * @return array{items: array<int, array<string, mixed>>, previous_cursor: string|null, next_cursor: string|null, current_cursor: string|null, invalid_cursor: bool}
     */
    public function listForUser(
        User $user,
        ?string $cursor = null,
        string $source = 'telegram_gateway',
        ?int $limit = null,
    ): array {
        $limit = $limit ?? ($source === 'whatsapp_gateway'
            ? self::WHATSAPP_LIMIT
            : self::TELEGRAM_LIMIT);
        $limit = min(max(1, $limit), self::WHATSAPP_LIMIT);
        $decoded = null;

        if (filled($cursor)) {
            $decoded = $this->cursors->decode((string) $cursor, $user, $source);
            if ($decoded === null) {
                return [
                    'items' => [],
                    'previous_cursor' => null,
                    'next_cursor' => null,
                    'current_cursor' => null,
                    'invalid_cursor' => true,
                ];
            }
        }

        $query = $this->ownedOrders($user)->with('pembayaran');
        $direction = $decoded['direction'] ?? 'older';

        if ($decoded !== null && $direction !== 'window') {
            $this->applyBoundary($query, $decoded);
        }

        if ($direction === 'window') {
            $this->applyWindow($query, $decoded);
        }

        if ($direction === 'newer') {
            $query->orderBy('created_at')->orderBy('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $orders = $query->limit($limit + 1)->get();
        $hasMoreInDirection = $orders->count() > $limit;
        $orders = $orders->take($limit);

        if ($direction === 'newer') {
            $orders = $orders->reverse()->values();
        } else {
            $orders = $orders->values();
        }

        $first = $orders->first();
        $last = $orders->last();
        $hasNewer = $first instanceof Pembelian
            && $this->hasNewerOrder($user, $first);
        $hasOlder = $last instanceof Pembelian
            && $this->hasOlderOrder($user, $last);

        if ($direction === 'newer' && $hasMoreInDirection) {
            $hasNewer = true;
        }
        if ($direction === 'older' && $hasMoreInDirection) {
            $hasOlder = true;
        }

        return [
            'items' => $orders
                ->map(fn (Pembelian $order): array => $this->summary($order))
                ->all(),
            'previous_cursor' => $hasNewer && $first instanceof Pembelian
                ? $this->cursorFor($first, 'newer', $user, $source)
                : null,
            'next_cursor' => $hasOlder && $last instanceof Pembelian
                ? $this->cursorFor($last, 'older', $user, $source)
                : null,
            'current_cursor' => $first instanceof Pembelian
                ? $this->cursorFor($first, 'window', $user, $source)
                : null,
            'invalid_cursor' => false,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(User $user, string $orderId): ?array
    {
        $orderId = trim($orderId);
        if ($orderId === '' || strlen($orderId) > 128 || preg_match('/[\x00-\x1F\x7F]/', $orderId) === 1) {
            return null;
        }

        $order = $this->ownedOrders($user)
            ->with('pembayaran')
            ->where('order_id', $orderId)
            ->first();

        return $order instanceof Pembelian ? $this->detail($order) : null;
    }

    public function findForUserByReference(User $user, string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '' || ! ctype_digit($reference) || strlen($reference) > 19) {
            return null;
        }

        $order = $this->ownedOrders($user)
            ->with('pembayaran')
            ->whereKey($reference)
            ->first();

        return $order instanceof Pembelian ? $this->detail($order) : null;
    }

    /**
     * @param array{direction: string, created_at: Carbon, id: string} $cursor
     */
    private function applyBoundary(Builder $query, array $cursor): void
    {
        $operator = $cursor['direction'] === 'newer' ? '>' : '<';
        $createdAt = $cursor['created_at'];
        $id = $cursor['id'];

        $query->where(function (Builder $boundary) use ($operator, $createdAt, $id): void {
            $boundary
                ->where('created_at', $operator, $createdAt)
                ->orWhere(function (Builder $tie) use ($operator, $createdAt, $id): void {
                    $tie->where('created_at', '=', $createdAt)
                        ->where('id', $operator, $id);
                });
        });
    }

    /**
     * @param array{direction: string, created_at: Carbon, id: string} $cursor
     */
    private function applyWindow(Builder $query, array $cursor): void
    {
        $createdAt = $cursor['created_at'];
        $id = $cursor['id'];

        $query->where(function (Builder $boundary) use ($createdAt, $id): void {
            $boundary
                ->where('created_at', '<', $createdAt)
                ->orWhere(function (Builder $tie) use ($createdAt, $id): void {
                    $tie->where('created_at', '=', $createdAt)
                        ->where('id', '<=', $id);
                });
        });
    }

    private function hasNewerOrder(User $user, Pembelian $first): bool
    {
        return $this->ownedOrders($user)
            ->where(function (Builder $query) use ($first): void {
                $query->where('created_at', '>', $first->created_at)
                    ->orWhere(function (Builder $tie) use ($first): void {
                        $tie->where('created_at', '=', $first->created_at)
                            ->where('id', '>', $first->getKey());
                    });
            })
            ->exists();
    }

    private function hasOlderOrder(User $user, Pembelian $last): bool
    {
        return $this->ownedOrders($user)
            ->where(function (Builder $query) use ($last): void {
                $query->where('created_at', '<', $last->created_at)
                    ->orWhere(function (Builder $tie) use ($last): void {
                        $tie->where('created_at', '=', $last->created_at)
                            ->where('id', '<', $last->getKey());
                    });
            })
            ->exists();
    }

    private function cursorFor(
        Pembelian $order,
        string $direction,
        User $user,
        string $source,
    ): string {
        return $this->cursors->encode([
            'created_at' => $order->created_at->toIso8601String(),
            'id' => (string) $order->getKey(),
        ], $direction, $user, $source);
    }

    private function ownedOrders(User $user): Builder
    {
        $query = Pembelian::query()
            ->where('username', (string) $user->username);

        if ($user->tenant_id !== null) {
            $query->where('pembelians.tenant_id', $user->tenant_id);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Pembelian $order): array
    {
        $status = PembelianStatus::normalize($order->status);

        return [
            'order_id' => $this->maskOrderId((string) $order->order_id),
            'reference' => (string) $order->getKey(),
            'order_key' => (string) $order->order_id,
            'service' => $this->safeText($order->layanan, 'Produk'),
            'created_at' => optional($order->created_at)->format('d/m/Y H:i'),
            'amount' => (int) $order->harga,
            'status' => $status,
            'status_label' => PembelianStatus::label($status),
            'payment_status' => $this->paymentStatus($order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Pembelian $order): array
    {
        $summary = $this->summary($order);
        $summary['order_id'] = (string) $order->order_id;
        return $summary;
    }

    private function paymentStatus(Pembelian $order): ?string
    {
        $status = trim((string) optional($order->pembayaran)->status);

        return $status === '' ? null : $this->safeText($status, null);
    }

    private function maskOrderId(string $orderId): string
    {
        $length = strlen($orderId);
        if ($length <= 5) {
            return str_repeat('•', $length);
        }

        return substr($orderId, 0, 2) . str_repeat('•', max(1, $length - 5)) . substr($orderId, -3);
    }

    private function safeText(mixed $value, ?string $fallback): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        return preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?: $fallback;
    }
}
