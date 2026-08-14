<?php

namespace App\Services\Order;

use App\Models\Pembelian;
use App\Models\User;
use App\Support\PembelianStatus;
use Illuminate\Database\Eloquent\Builder;

class OrderHistoryService
{
    public const DEFAULT_PER_PAGE = 5;

    public const MAX_PER_PAGE = 5;

    /**
     * @return array{items: array<int, array<string, mixed>>, page: int, per_page: int, total: int, total_pages: int}
     */
    public function listForUser(User $user, int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $perPage = min(max(1, $perPage), self::MAX_PER_PAGE);
        $page = max(1, $page);
        $query = $this->ownedOrders($user)
            ->with('pembayaran')
            ->latest('created_at')
            ->latest('id');

        $total = (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $orders = $query
            ->forPage($page, $perPage)
            ->get();

        return [
            'items' => $orders->map(fn (Pembelian $order): array => $this->summary($order))->values()->all(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
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
