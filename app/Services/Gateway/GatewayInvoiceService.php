<?php

namespace App\Services\Gateway;

use App\Models\Pembelian;
use App\Models\User;
use App\Services\Checkout\CheckoutOrderService;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class GatewayInvoiceService
{
    private const ALLOWED_SOURCES = ['whatsapp_gateway', 'telegram_gateway'];

    public function __construct(
        private readonly CheckoutOrderService $checkout,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function createInvoice(array $payload, ?User $user = null, string $source = 'whatsapp_gateway', array $context = []): array
    {
        $source = $this->normalizeSource($source);
        $checkoutPayload = $payload;

        if (isset($checkoutPayload['service_id']) && ! isset($checkoutPayload['service'])) {
            $checkoutPayload['service'] = $checkoutPayload['service_id'];
        }

        $context = $this->normalizeContext($context + [
            'external_user_id' => $payload['external_user_id']
                ?? null,
            'channel' => $this->channelFromSource($source),
            'message_id' => $payload['message_id'] ?? null,
            'intent_id' => $payload['intent_id'] ?? null,
        ], $source);

        $result = $this->checkout->createFromPayload($checkoutPayload, $user, $source, $context);

        return [
            'ok' => (bool) ($result['status'] ?? false),
            'message' => (string) ($result['message'] ?? 'Invoice berhasil dibuat.'),
            'data' => $result,
        ];
    }

    public function status(string $orderId, ?User $user = null, array $context = []): array
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            throw ValidationException::withMessages([
                'order_id' => 'Order ID wajib diisi.',
            ]);
        }

        $order = Pembelian::query()
            ->with('pembayaran')
            ->where(function (Builder $query) use ($orderId): void {
                $query->where('order_id', $orderId)
                    ->orWhere('display_order_id', $orderId);
            })
            ->when($this->tenantContext->id() !== null, function (Builder $query): void {
                $query->where('tenant_id', $this->tenantContext->id());
            })
            ->first();

        if (! $order) {
            return [
                'ok' => false,
                'error_code' => 'INVOICE_NOT_FOUND',
                'message' => 'Invoice tidak ditemukan.',
                'data' => null,
            ];
        }

        $this->authorizeStatusLookup($order, $user, $context);

        if ($order->pembayaran) {
            $order->pembayaran->syncExpiredStatus();
            $order->refresh()->load('pembayaran');
        }

        return [
            'ok' => true,
            'message' => 'Status invoice berhasil dimuat.',
            'data' => $this->checkout->statusPayload($order),
        ];
    }

    public function latestForSender(string $source, string $externalUserId): ?Pembelian
    {
        $source = $this->normalizeSource($source);
        $externalUserId = $this->normalizeExternalUserId($source, $externalUserId);
        $senderDigits = preg_replace('/\D+/', '', $externalUserId);

        if ($senderDigits === '') {
            return null;
        }

        $query = Pembelian::query()
            ->where('traffic_source', $source)
            ->whereHas('pembayaran', function (Builder $query) use ($senderDigits): void {
                $query->where('no_pembeli', $senderDigits);
            })
            ->when($this->tenantContext->id() !== null, function (Builder $query): void {
                $query->where('tenant_id', $this->tenantContext->id());
            })
            ->latest('created_at');

        return $query->first();
    }

    /**
     * Order milik sender yang masih "aktif" (belum selesai/tuntas):
     * pembayaran belum lunas, atau order status masih proses/sukses-belum-selesai.
     * Dipakai handler `status` tanpa argumen untuk menampilkan daftar pilihan.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pembelian>
     */
    public function activeOrdersForSender(string $source, string $externalUserId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $source = $this->normalizeSource($source);
        $externalUserId = $this->normalizeExternalUserId($source, $externalUserId);

        if ($source === 'telegram_gateway') {
            // Aktif untuk Telegram: principal cocok DAN order belum
            // final. Pembayaran Telegram tidak memakai no_pembeli,
            // jadi filter status cukup dari kolom order.
            return $this->recentOrdersForTelegramPrincipal($externalUserId, $limit)
                ->filter(fn (Pembelian $order): bool => ! in_array(
                    strtolower(trim((string) $order->status)),
                    ['sukses', 'success', 'selesai', 'gagal', 'batal', 'cancel', 'expired'],
                    true,
                ))
                ->values();
        }

        $senderDigits = preg_replace('/\D+/', '', $externalUserId);

        if ($senderDigits === '') {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return Pembelian::query()
            ->with('pembayaran')
            ->where('traffic_source', $source)
            ->where(function (Builder $query) use ($senderDigits): void {
                // Identitas WA setara: no_pembeli di pembayaran (checkout
                // normal), ATAU gateway_principal / legacy email untuk
                // order manual-joki yang tidak punya row pembayaran.
                $query->whereHas('pembayaran', function (Builder $query) use ($senderDigits): void {
                    $query->where('no_pembeli', $senderDigits);
                })->orWhere('gateway_principal', 'whatsapp:' . $senderDigits)
                    ->orWhere('email_pembeli', $senderDigits . '@whatsapp.user');
            })
            ->when($this->tenantContext->id() !== null, function (Builder $query): void {
                $query->where('tenant_id', $this->tenantContext->id());
            })
            ->where(function (Builder $query): void {
                // Aktif = pembayaran belum final ATAU order belum final.
                // Final payment: Lunas / Expired / Kadaluarsa / Gagal / Batal
                // Final order:   Sukses / Selesai / Gagal / Batal / Cancel / Expired
                $query->where(function (Builder $query): void {
                    $query->whereHas('pembayaran', function (Builder $query): void {
                        $query->whereNotIn('status', ['Lunas', 'Expired', 'Kadaluarsa', 'Gagal', 'Batal', 'Cancel']);
                    });
                })->orWhere(function (Builder $query): void {
                    // Tanpa row pembayaran (manual/joki): keaktifan
                    // dinilai dari status order saja.
                    $query->whereDoesntHave('pembayaran')
                        ->whereNotIn('status', ['Sukses', 'Selesai', 'Gagal', 'Batal', 'Cancel', 'Expired']);
                })->orWhere(function (Builder $query): void {
                    $query->whereHas('pembayaran', function (Builder $query): void {
                        $query->where('status', 'Lunas');
                    })
                        ->whereNotIn('status', ['Sukses', 'Selesai', 'Gagal', 'Batal', 'Cancel', 'Expired']);
                });
            })
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Order terakhir milik sender tanpa filter status (semua status).
     * Dipakai handler `status` tanpa argumen ketika tidak ada order
     * aktif: user tetap melihat riwayat singkat miliknya.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pembelian>
     */
    public function recentOrdersForSender(string $source, string $externalUserId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $source = $this->normalizeSource($source);
        $externalUserId = $this->normalizeExternalUserId($source, $externalUserId);

        if ($source === 'telegram_gateway') {
            return $this->recentOrdersForTelegramPrincipal($externalUserId, $limit);
        }

        $senderDigits = preg_replace('/\D+/', '', $externalUserId);

        if ($senderDigits === '') {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return Pembelian::query()
            ->with('pembayaran')
            ->where('traffic_source', $source)
            ->where(function (Builder $query) use ($senderDigits): void {
                // Identitas WA setara: no_pembeli di pembayaran (checkout
                // normal), ATAU gateway_principal / legacy email untuk
                // order manual-joki yang tidak punya row pembayaran.
                $query->whereHas('pembayaran', function (Builder $query) use ($senderDigits): void {
                    $query->where('no_pembeli', $senderDigits);
                })->orWhere('gateway_principal', 'whatsapp:' . $senderDigits)
                    ->orWhere('email_pembeli', $senderDigits . '@whatsapp.user');
            })
            ->when($this->tenantContext->id() !== null, function (Builder $query): void {
                $query->where('tenant_id', $this->tenantContext->id());
            })
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Recent orders untuk sender Telegram. Identitas primer adalah
     * gateway_principal (telegram:<id>); order lama sebelum kolom
     * principal ada masih memakai email legacy telegram:<id>@telegram.user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pembelian>
     */
    private function recentOrdersForTelegramPrincipal(string $principal, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $legacyEmail = $this->telegramEmailForPrincipal($principal);

        return Pembelian::query()
            ->with('pembayaran')
            ->where('traffic_source', 'telegram_gateway')
            ->where(function (Builder $query) use ($principal, $legacyEmail): void {
                $query->where('gateway_principal', $principal);

                if ($legacyEmail !== null) {
                    $query->orWhere('email_pembeli', $legacyEmail);
                }
            })
            ->when($this->tenantContext->id() !== null, function (Builder $query): void {
                $query->where('tenant_id', $this->tenantContext->id());
            })
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    private function authorizeStatusLookup(Pembelian $order, ?User $user, array $context): void
    {
        if ($user) {
            if ((string) $order->username !== (string) $user->username) {
                throw ValidationException::withMessages([
                    'order_id' => 'Invoice tidak dapat diakses oleh user ini.',
                ]);
            }

            return;
        }

        $source = trim((string) ($context['source'] ?? ''));
        if ($source !== '') {
            $source = $this->normalizeSource($source);
            if ((string) $order->traffic_source !== $source) {
                throw ValidationException::withMessages([
                    'order_id' => 'Invoice tidak dapat diakses dari source ini.',
                ]);
            }
        }

        $externalUserId = $this->normalizeExternalUserId($source, $context['external_user_id'] ?? null);
        if ($externalUserId === '') {
            throw ValidationException::withMessages([
                'order_id' => 'Invoice tidak dapat diakses tanpa identitas sender.',
            ]);
        }

        if ($source === 'telegram_gateway') {
            $storedPrincipal = trim((string) $order->gateway_principal);
            if ($storedPrincipal !== '') {
                if (hash_equals($storedPrincipal, $externalUserId)) {
                    return;
                }

                throw ValidationException::withMessages([
                    'order_id' => 'Invoice tidak dapat diakses oleh sender ini.',
                ]);
            }

            $legacyEmail = $this->telegramEmailForPrincipal($externalUserId);
            if ($legacyEmail !== null && hash_equals($legacyEmail, trim((string) $order->email_pembeli))) {
                return;
            }

            throw ValidationException::withMessages([
                'order_id' => 'Invoice tidak dapat diakses oleh sender ini.',
            ]);
        }

        $metadata = json_decode((string) $order->log, true);
        $gatewayContext = is_array($metadata) ? ($metadata['gateway_context'] ?? []) : [];
        $storedExternalUserId = $this->normalizeExternalUserId($source, $gatewayContext['external_user_id'] ?? null);

        if (is_array($gatewayContext) && hash_equals($storedExternalUserId, $externalUserId)) {
            return;
        }

        // Fallback: gateway_context may be lost when the order log is
        // overwritten by provider dispatch results. For WhatsApp, the
        // buyer's phone (pembayaran.no_pembeli) is a stable sender identity.
        if ($source === 'whatsapp_gateway') {
            $senderDigits = preg_replace('/\D+/', '', $externalUserId);

            if ($senderDigits !== '') {
                // Order manual/joki tidak punya row pembayaran; cocokkan
                // lewat gateway_principal / legacy email sebagai gantinya.
                $storedPrincipal = trim((string) $order->gateway_principal);
                if ($storedPrincipal !== '' && hash_equals($storedPrincipal, 'whatsapp:' . $senderDigits)) {
                    return;
                }

                if (hash_equals(
                    strtolower(trim((string) $order->email_pembeli)),
                    strtolower($senderDigits . '@whatsapp.user'),
                )) {
                    return;
                }

                $noPembeli = preg_replace('/\D+/', '', (string) ($order->pembayaran?->no_pembeli ?? ''));

                if ($noPembeli !== '' && hash_equals($noPembeli, $senderDigits)) {
                    return;
                }
            }
        }

        throw ValidationException::withMessages([
            'order_id' => 'Invoice tidak dapat diakses oleh sender ini.',
        ]);
    }

    private function normalizeContext(array $context, string $source): array
    {
        $context['source'] = $source;
        $context['channel'] = trim(
            (string) ($context['channel'] ?? ''),
        ) ?: $this->channelFromSource($source);
        $context['external_user_id'] = $this->normalizeExternalUserId(
            $source,
            $context['external_user_id'] ?? null,
        );
        $context['message_id'] = trim(
            (string) ($context['message_id'] ?? ''),
        );
        $context['intent_id'] = trim(
            (string) ($context['intent_id'] ?? ''),
        );

        return $context;
    }

    private function normalizeExternalUserId(?string $source, mixed $externalUserId): string
    {
        $externalUserId = trim((string) $externalUserId);

        if ($source !== 'telegram_gateway' || $externalUserId === '') {
            return $externalUserId;
        }

        if (preg_match('/^(?:telegram:)?(\d+)$/', $externalUserId, $matches) !== 1) {
            return $externalUserId;
        }

        return 'telegram:' . $matches[1];
    }

    private function telegramEmailForPrincipal(string $principal): ?string
    {
        if (preg_match('/^telegram:(\d+)$/', $principal, $matches) !== 1) {
            return null;
        }

        return 'telegram:' . $matches[1] . '@telegram.user';
    }

    private function normalizeSource(string $source): string
    {
        $source = strtolower(trim($source));

        if (! in_array($source, self::ALLOWED_SOURCES, true)) {
            throw ValidationException::withMessages([
                'source' => 'Source gateway tidak valid.',
            ]);
        }

        return $source;
    }

    private function channelFromSource(string $source): string
    {
        return match ($source) {
            'telegram_gateway' => 'telegram',
            default => 'whatsapp',
        };
    }
}
