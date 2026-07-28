<?php

namespace App\Services\Bot;

use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayCheckIdService;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\PaymentMethodCatalogService;
use Illuminate\Validation\ValidationException;

class BotCommandHandler
{
    public function __construct(
        private readonly GatewayCatalogService $catalog,
        private readonly PaymentMethodCatalogService $payment,
        private readonly GatewayPricingService $pricing,
        private readonly GatewayCheckIdService $checkId,
        private readonly GatewayInvoiceService $invoice,
        private readonly BotMessageFormatter $formatter
    ) {}

    /**
     * @param string $command
     * @param array $args
     * @param array $context {source: string, external_user_id: string, message_id?: string, nomor?: string, whatsapp?: string, email?: string}
     * @return array{text: string, buttons: array}
     */
    public function handle(?string $command, array $args, array $context): array
    {
        try {
            return match ($command) {
                'start', 'help', 'bantuan' => $this->formatter->formatHelp(),
                'menu' => $this->handleMenu($args),
                'kategori' => $this->handleKategori($args),
                'layanan', 'produk' => $this->handleLayanan($args),
                'pembayaran', 'metode' => $this->handlePembayaran($args),
                'harga', 'price' => $this->handleHarga($args),
                'cekid' => $this->handleCekId($args),
                'invoice', 'beli' => $this->handleInvoice($args, $context),
                'status' => $this->handleStatus($args, $context),
                default => [
                    'text' => "Perintah tidak dikenali.\nSilahkan gunakan menu utama.",
                    'buttons' => [['text' => 'Buka Menu', 'callback' => 'menu']]
                ],
            };
        } catch (ValidationException $e) {
            $err = collect($e->errors())->flatten()->first();
            return [
                'text' => "Validasi gagal: " . $err,
                'buttons' => [['text' => 'Kembali ke Menu', 'callback' => 'menu']]
            ];
        } catch (\Throwable $e) {
            return [
                'text' => "Terjadi kesalahan internal: " . $e->getMessage(),
                'buttons' => []
            ];
        }
    }

    private function handleMenu(array $args = []): array
    {
        $res = $this->catalog->categoryTypes();
        return $this->formatter->formatCategories($res, $this->pageFromArgs($args));
    }

    private function handleKategori(array $args): array
    {
        $type = $args[0] ?? null;
        if (! $type) {
            return [
                'text' => "Format salah. Gunakan: `kategori <kode_tipe>`\nContoh: `kategori top-up-games`",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $res = $this->catalog->categories(null, ['type' => $type]);
        return $this->formatter->formatProducts($res, $this->pageFromArgs($args));
    }

    private function handleLayanan(array $args): array
    {
        $catCode = $args[0] ?? null;
        if (! $catCode) {
            return [
                'text' => "Format salah. Gunakan: `layanan <kode_produk>`",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $res = $this->catalog->services($catCode);
        return $this->formatter->formatServices($res, $this->pageFromArgs($args));
    }

    private function handlePembayaran(array $args): array
    {
        $serviceId = (int) ($args[0] ?? 0);
        if ($serviceId <= 0) {
            return [
                'text' => "Format salah. Pilih layanan terlebih dahulu.",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $methods = $this->payment->getVisibleMethods()->filter(function ($m) {
            return !$m->isSaldoMethod();
        })->map(function ($m) {
            return ['name' => $m->name, 'code' => $m->code];
        })->values()->all();

        $service = $this->catalog->serviceById($serviceId);
        $backCallback = isset($service['data']['category']['code'])
            ? 'layanan ' . $service['data']['category']['code']
            : null;

        return $this->formatter->formatPaymentMethods(['ok' => true, 'data' => $methods], $serviceId, $this->pageFromArgs($args), $backCallback);
    }

    private function handleHarga(array $args): array
    {
        if (count($args) < 2) {
            return [
                'text' => "Format salah. Gunakan: `harga <ID_Layanan> <Kode_Bayar>`",
                'buttons' => [['text' => 'Lihat Menu', 'callback' => 'menu']]
            ];
        }

        $payload = [
            'service_id' => $args[0],
            'payment_method' => $args[1],
        ];

        $res = $this->pricing->quote($payload, null);
        return $this->formatter->formatPriceQuote($res);
    }

    private function handleCekId(array $args): array
    {
        if (count($args) < 2) {
            return [
                'text' => "Format salah. Gunakan: `cekid <kode_produk> <uid> [zone]`\nContoh: `cekid mobile-legends 1234567 1234`",
                'buttons' => []
            ];
        }

        $payload = [
            'category_code' => $args[0],
            'uid' => $args[1],
            'zone' => $args[2] ?? null,
        ];

        $res = $this->checkId->check($payload);
        return $this->formatter->formatCheckId($res);
    }

    private function handleInvoice(array $args, array $context): array
    {
        if (count($args) < 3) {
            return [
                'text' => "Format salah. Gunakan: `invoice <id_layanan> <kode_bayar> <uid> [zone]`",
                'buttons' => []
            ];
        }

        $payload = [
            'service_id' => $args[0],
            'payment_method' => $args[1],
            'uid' => $args[2],
            'zone' => $args[3] ?? null,
            'source' => $context['source'],
            'external_user_id' => $context['external_user_id'],
            'message_id' => $context['message_id'] ?? null,
        ];

        foreach (['nomor', 'whatsapp', 'email'] as $contactField) {
            if (filled($context[$contactField] ?? null)) {
                $payload[$contactField] = $context[$contactField];
            }
        }

        $res = $this->invoice->createInvoice($payload, null, $context['source'], $payload);
        return $this->formatter->formatInvoice($res);
    }

    private function handleStatus(array $args, array $context): array
    {
        if (count($args) < 1) {
            return [
                'text' => "Format salah. Gunakan: `status <order_id>`",
                'buttons' => []
            ];
        }

        $res = $this->invoice->status($args[0], null, [
            'source' => $context['source'],
            'external_user_id' => $context['external_user_id'],
        ]);

        return $this->formatter->formatStatus($res);
    }

    private function pageFromArgs(array $args): int
    {
        foreach ($args as $arg) {
            if (preg_match('/^page:(\d+)$/', (string) $arg, $matches)) {
                return max(1, (int) $matches[1]);
            }
        }

        return 1;
    }
}
