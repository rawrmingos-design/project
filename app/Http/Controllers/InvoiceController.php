<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\Berita;
use App\Models\Method;
use Illuminate\Support\Carbon;
use App\Models\Layanan;
use App\Models\Kategori;
use App\Support\GtmDataLayerBuilder;
use App\Support\InvoiceRealtimeStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    private function applyMethodsJoin($query)
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $query
                ->leftJoin('methods', 'pembayarans.metode', '=', 'methods.code')
                ->leftJoin('payment_display_categories', 'methods.payment_display_category_id', '=', 'payment_display_categories.id');
        }

        return $query
            ->leftJoin(
                'methods',
                DB::raw('pembayarans.metode COLLATE utf8mb4_unicode_ci'),
                '=',
                DB::raw('methods.code COLLATE utf8mb4_unicode_ci')
            )
            ->leftJoin('payment_display_categories', 'methods.payment_display_category_id', '=', 'payment_display_categories.id');
    }

   public function create($order)
    {
        $payment = Pembayaran::query()
            ->where('order_id', $order)
            ->latest('id')
            ->first();

        abort_if(! $payment, 404);

        $payment->syncExpiredStatus();

        $dataQuery = Pembelian::where('pembayarans.order_id', $order)
            ->join('pembayarans', 'pembelians.order_id', '=', 'pembayarans.order_id')
            ->leftJoin('data_joki', 'pembelians.order_id', '=', 'data_joki.order_id');

        $data = $this->applyMethodsJoin($dataQuery)
            ->select(
                'data_joki.*',
                'pembayarans.status AS status_pembayaran',
                'pembayarans.metode AS metode_pembayaran',
                'pembayarans.no_pembayaran',
                'pembayarans.reference',
                'pembayarans.expired_at',
                'pembelians.order_id AS id_pembelian',
                'user_id',
                'zone',
                'nickname',
                'voucher',
                'layanan',
                'pembayarans.harga AS harga_pembayaran',
                'pembelians.keterangan_sn',
                'pembelians.created_at AS created_at',
                'pembelians.status AS status_pembelian',
                'pembayarans.reference',
                'pembayarans.no_pembeli',
                'pembelians.email_pembeli',
                'pembelians.tipe_transaksi AS tipe_transaksi',
                'methods.name AS metode_name',
                'methods.payment_display_category_id',
                'payment_display_categories.id AS metode_category_id',
                'payment_display_categories.label AS metode_category_label',
                'payment_display_categories.icon AS metode_category_icon',
                'payment_display_categories.code AS metode_category_code'
            )
            ->orderByDesc('pembayarans.id')
            ->first();

        abort_if(! $data, 404);

        $layanan = Layanan::query()
            ->with('kategori:id,nama,thumbnail')
            ->where('layanan', $data->layanan)
            ->first();

        $kategori = $layanan?->kategori;

        $nama = $kategori?->nama ?: ($data->layanan ?: 'Produk');
        $thumbnail = $kategori?->thumbnail ?: 'assets/logo/favicon.webp';
        $methodCode = trim((string) ($data->metode_pembayaran ?? ''));
        $methodName = $data->metode_name;

        if (blank($methodName) && $methodCode !== '') {
            $matchedMethod = Method::query()
                ->get(['code', 'name'])
                ->first(function (Method $method) use ($methodCode) {
                    $rawCode = trim((string) ($method->getRawOriginal('code') ?? $method->code));
                    $rawName = trim((string) ($method->getRawOriginal('name') ?? $method->name));

                    return strcasecmp($rawCode, $methodCode) === 0
                        || strcasecmp($rawName, $methodCode) === 0;
                });

            $methodName = $matchedMethod?->name;
        }

        if (blank($methodName)) {
            $methodName = Str::of($methodCode)
                ->replace(['_', '-'], ' ')
                ->squish()
                ->title()
                ->value();
        }

        if (blank($methodName)) {
            $methodName = 'Metode Tidak Dikenal';
        }

        $expired = $data->expired_at
            ? Carbon::parse($data->expired_at)
            : Carbon::create($data->created_at)->addHours(3);

        $gtmBuilder = app(GtmDataLayerBuilder::class);
        $gtmInvoiceItem = $gtmBuilder->buildItem([
            'item_id' => $layanan?->id ?: $data->id_pembelian,
            'item_name' => $data->layanan ?: $nama,
            'item_category' => $kategori?->nama ?: $nama,
            'item_variant' => $data->tipe_transaksi ?? null,
            'price' => $data->harga_pembayaran ?? 0,
            'quantity' => 1,
        ]);

        $normalizedPaymentStatus = Str::lower(trim((string) ($data->status_pembayaran ?? '')));
        $normalizedOrderStatus = Str::lower(trim((string) ($data->status_pembelian ?? '')));
        $transactionId = (string) $data->id_pembelian;
        $invoiceValue = (int) round((float) ($data->harga_pembayaran ?? 0));
        $gtmIdentityPayload = $gtmBuilder->buildCustomerIdentityPayload(
            $data->email_pembeli ?? null,
            $data->no_pembeli ?? null,
            $data->user_id ?? null,
            $data->zone ?? null,
            $data->nickname ?? null,
        );

        $gtmInvoiceEvents = [
            [
                'name' => 'invoice_viewed',
                'payload' => $gtmBuilder->buildInvoiceViewedPayload(
                    $transactionId,
                    $methodName,
                    (string) ($data->status_pembayaran ?? ''),
                    (string) ($data->status_pembelian ?? ''),
                    $invoiceValue,
                    $gtmInvoiceItem,
                    'IDR',
                    $gtmIdentityPayload,
                ),
                'dedupe_key' => "invoice_viewed:{$transactionId}",
            ],
        ];

        if (in_array($normalizedPaymentStatus, ['belum lunas', 'unpaid', 'pending'], true)) {
            $gtmInvoiceEvents[] = [
                'name' => 'add_payment_info',
                'payload' => $gtmBuilder->buildAddPaymentInfoPayload(
                    $transactionId,
                    $methodName,
                    $invoiceValue,
                    $gtmInvoiceItem,
                    'IDR',
                    $gtmIdentityPayload,
                ),
                'dedupe_key' => "add_payment_info:{$transactionId}",
            ];
            $gtmInvoiceEvents[] = [
                'name' => 'payment_pending',
                'payload' => $gtmBuilder->buildOperationalPayload(
                    $transactionId,
                    $methodName,
                    (string) ($data->status_pembayaran ?? ''),
                    (string) ($data->status_pembelian ?? ''),
                    $invoiceValue,
                    $gtmInvoiceItem,
                    'IDR',
                    $gtmIdentityPayload,
                ),
                'dedupe_key' => "payment_pending:{$transactionId}",
            ];
        }

        if (in_array($normalizedPaymentStatus, ['paid', 'lunas'], true)) {
            $gtmInvoiceEvents[] = [
                'name' => 'payment_success',
                'payload' => $gtmBuilder->buildOperationalPayload(
                    $transactionId,
                    $methodName,
                    (string) ($data->status_pembayaran ?? ''),
                    (string) ($data->status_pembelian ?? ''),
                    $invoiceValue,
                    $gtmInvoiceItem,
                    'IDR',
                    $gtmIdentityPayload,
                ),
                'dedupe_key' => "payment_success:{$transactionId}",
            ];

            if (in_array($normalizedOrderStatus, ['sukses', 'success'], true)) {
                $gtmInvoiceEvents[] = [
                    'name' => 'purchase',
                    'payload' => $gtmBuilder->buildPurchasePayload(
                        $transactionId,
                        $methodName,
                        $invoiceValue,
                        $gtmInvoiceItem,
                        'IDR',
                        (string) ($data->status_pembayaran ?? ''),
                        (string) ($data->status_pembelian ?? ''),
                        $gtmIdentityPayload,
                    ),
                    'dedupe_key' => "purchase:{$transactionId}",
                ];
            }
        }

        if (in_array($normalizedPaymentStatus, ['expired'], true)) {
            $gtmInvoiceEvents[] = [
                'name' => 'payment_expired',
                'payload' => $gtmBuilder->buildOperationalPayload(
                    $transactionId,
                    $methodName,
                    (string) ($data->status_pembayaran ?? ''),
                    (string) ($data->status_pembelian ?? ''),
                    $invoiceValue,
                    $gtmInvoiceItem,
                    'IDR',
                    $gtmIdentityPayload,
                ),
                'dedupe_key' => "payment_expired:{$transactionId}",
            ];
        }

        if (in_array($normalizedOrderStatus, ['proses', 'processing'], true)) {
            $gtmInvoiceEvents[] = [
                'name' => 'order_processing',
                'payload' => $gtmBuilder->buildOperationalPayload(
                    $transactionId,
                    $methodName,
                    (string) ($data->status_pembayaran ?? ''),
                    (string) ($data->status_pembelian ?? ''),
                    $invoiceValue,
                    $gtmInvoiceItem,
                    'IDR',
                    $gtmIdentityPayload,
                ),
                'dedupe_key' => "order_processing:{$transactionId}",
            ];
        }

        if (in_array($normalizedOrderStatus, ['gagal', 'failed', 'batal', 'cancelled'], true)) {
            $gtmInvoiceEvents[] = [
                'name' => 'order_failed',
                'payload' => $gtmBuilder->buildOperationalPayload(
                    $transactionId,
                    $methodName,
                    (string) ($data->status_pembayaran ?? ''),
                    (string) ($data->status_pembelian ?? ''),
                    $invoiceValue,
                    $gtmInvoiceItem,
                    'IDR',
                    $gtmIdentityPayload,
                ),
                'dedupe_key' => "order_failed:{$transactionId}",
            ];
        }

        return view('template.invoice', [
            'data' => $data,
            'expired' => $expired,
            'expiredIso' => $expired->toIso8601String(),
            'namas' => $nama,
            'thumbnails' => $thumbnail,
            'metode_name' => $methodName,
            'metode_category_label' => (string) ($data->metode_category_label ?? ''),
            'metode_category_icon' => (string) ($data->metode_category_icon ?? ''),
            'metode_category_code' => (string) ($data->metode_category_code ?? ''),
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'order_id' => $data->id_pembelian,
            'gtmInvoiceEvents' => $gtmInvoiceEvents,
            'gtmInvoiceItem' => $gtmInvoiceItem,
            'invoiceRealtimeChannel' => InvoiceRealtimeStatus::channelName($transactionId),
        ]);

    }


   public function ratingCustomer(Request $request, $order_id) {
    $input = $request->all();
    $wantsJson = $request->expectsJson() || $request->ajax();

    $validator = Validator::make($input, [
        'bintang' => 'required|integer|min:1|max:5',
        'comment' => 'required|string',
        'kategori_nama' => 'required',
    ]);

    if ($validator->fails()) {
        if ($wantsJson) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih bintang dan isi komentar terlebih dahulu.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return redirect()->back()->withInput()->withErrors($validator);
    }

    $existingRating = DB::table('ratings')->where('rating_id', $order_id)->first();

    if ($existingRating) {
        if ($wantsJson) {
            return response()->json([
                'success' => false,
                'message' => 'Review untuk transaksi ini sudah pernah dikirim.',
                'already_reviewed' => true,
            ], 409);
        }

        return redirect()->back()->with('error', 'Review untuk transaksi ini sudah pernah dikirim.');
    }

    $bintang = $input['bintang'];
    $comment = $input['comment'];
    $kategori_nama = $input['kategori_nama'];

    $kategori = Kategori::where('nama', $kategori_nama)->first();

    if ($kategori) {
        $pembelian = Pembelian::where('order_id', $order_id)->first();
        $pembayaran = Pembayaran::where('order_id', $order_id)->first();

        if ($pembelian && $pembayaran) {
            $username = $pembelian->username ? $pembelian->username : $pembayaran->no_pembeli;

            $ratingId = DB::table('ratings')->insertGetId([
                'bintang' => $bintang,
                'comment' => $comment,
                'rating_id' => $order_id,
                'kategori_id' => $kategori->id,
                'username' => $username,
                'layanan' => $pembelian->layanan,
                'no_pembeli' => $pembayaran->no_pembeli
            ]);

            $rating = DB::table('ratings')->where('id', $ratingId)->first();

            if ($wantsJson) {
                return response()->json([
                    'success' => true,
                    'message' => 'Terima kasih telah memberikan testimoni!',
                    'rating' => $rating,
                ]);
            }

            return redirect()->back()->with('success', 'Terima kasih telah memberikan testimoni!')->with('rating', $rating);
        } else {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pembelian atau pembayaran tidak lengkap atau tidak ditemukan!',
                ], 404);
            }

            return redirect()->back()->withInput()->with('error', 'Data pembelian atau pembayaran tidak lengkap atau tidak ditemukan!');
        }
    } else {
        if ($wantsJson) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan!',
            ], 404);
        }

        return redirect()->back()->withInput()->with('error', 'Kategori tidak ditemukan!');
    }
}

    public function checkStatus($order)
    {
        $payment = Pembayaran::query()
            ->where('order_id', $order)
            ->latest('id')
            ->first();

        if ($payment) {
            $payment->syncExpiredStatus();
        }

        $data = Pembelian::where('pembayarans.order_id', $order)
            ->join('pembayarans', 'pembelians.order_id', '=', 'pembayarans.order_id')
            ->select('pembayarans.status AS status_pembayaran', 'pembelians.status AS status_pembelian')
            ->orderByDesc('pembayarans.id')
            ->first();

        if ($data) {
            return response()->json([
                'success' => true,
                'status_pembayaran' => $data->status_pembayaran,
                'status_pembelian' => $data->status_pembelian
            ]);
        }

        return response()->json(['success' => false], 404);
    }
}
