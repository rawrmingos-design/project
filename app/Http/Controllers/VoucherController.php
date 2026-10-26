<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Models\Layanan;
use App\Tenancy\TenantPricingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function create()
    {
        return view('components.admin.voucher', [
            // `layanan_id` kosong = kupon platform untuk SEMUA produk; kalau
            // diisi, kupon hanya berlaku di produk itu (ditegakkan di
            // `VoucherResolver`, bukan sekadar tampilan form).
            'layanans' => Layanan::query()->orderBy('layanan')->get(['id', 'layanan']),
            'vouchers' => Voucher::query()
                ->whereNull('tenant_id')
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }
    
    public function store(Request $request)
    {
        // Voucher yang dibuat dari panel admin selalu milik platform, jadi
        // keunikannya hanya dibanding voucher platform lain. Tanpa `where`,
        // kode yang sudah dipakai seorang tenant akan salah ditolak.
        $request->validate([
            'kode' => ['required', Rule::unique('vouchers', 'kode')->whereNull('tenant_id')],
            'promo' => 'required|numeric|min:0|max:100',
            'stock' => 'required|numeric|min:0',
            'max_potongan' => 'required|numeric|min:0',
            'expired_at' => 'nullable|date',
            // Kosong = berlaku di semua produk. Diisi = hanya produk itu.
            'layanan_id' => ['nullable', 'integer', 'exists:layanans,id'],
        ]);

        $voucher = new Voucher();
        $voucher->layanan_id = $request->filled('layanan_id') ? (int) $request->layanan_id : null;
        $voucher->kode = $request->kode;
        $voucher->promo = $request->promo;
        $voucher->stock = $request->stock;
        $voucher->mintrx = $request->mintrx;
        $voucher->max_potongan = $request->max_potongan;
        $voucher->expired_at = $request->expired_at ?: null;
        $voucher->save();
        
        return back()->with('success', 'Berhasil menambahkan voucher');
    }
    
    public function destroy($id)
    {
        Voucher::where('id', $id)->delete();
        
        return back()->with('success', 'Berhasil menghapus voucher');
    }
    
    public function confirm(Request $request)
    {
        $request->validate([
            'voucher' => 'required'
        ]);
        
        // Resolusi terpusat: di situs tenant hanya voucher MILIK TENANT ITU yang
        // boleh ditemukan; di situs platform hanya voucher platform. Kupon tidak
        // pernah menyeberang (lihat Voucher::scopeAvailableTo).
        $voucher = app(\App\Services\Voucher\VoucherResolver::class)
            ->resolve(
                app()->bound(\App\Tenancy\TenantContext::class) ? app(\App\Tenancy\TenantContext::class)->id() : null,
                $request->voucher
            );

        if(!$voucher) return response()->json(['status' => false, 'message' => 'Voucher tidak ditemukan'], 404);
        if($voucher->stock <= 0) return response()->json(['status' => false, 'message' => 'Voucher sudah tidak valid'], 404);
        if($voucher->isExpired()) return response()->json(['status' => false, 'message' => 'Voucher sudah kadaluarsa'], 422);

        if(isset($request->service)){
            // SATU SUMBER HARGA. Sebelum ini blok di sini menyalin
            // `match($role) { 'Gold' => harga_gold, ... }` dan tidak pernah
            // memanggil `TenantPricingService`, jadi di toko tenant potongan
            // kupon dihitung dari harga PLATFORM (100.000) padahal yang
            // ditagih harga jual tenant (105.000):
            //   - mintrx dibandingkan ke angka yang lebih kecil -> kupon yang
            //     sebenarnya sah DITOLAK di layar pratinjau
            //   - potongan dihitung dari angka yang salah -> nominal di layar
            //     berbeda dari nominal yang dipotong saat checkout
            //   - lantai modal tenant tidak pernah diperiksa -> pratinjau
            //     menyatakan sah kupon yang akan gagal di checkout
            // `priceForRole()` sudah jadi tempat tunggal aturan ini, dan ia
            // membaca anchor modal dari `getOriginal('harga')` sehingga aman
            // dipanggil berkali-kali.
            $layanan = Layanan::query()->find($request->service);

            if ($layanan === null) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Produk tidak ditemukan',
                ], 404);
            }

            $tenant = app()->bound(\App\Tenancy\TenantContext::class)
                ? app(\App\Tenancy\TenantContext::class)->get()
                : null;

            $role = Auth::check() ? Auth::user()->role : 'Guest';
            $price = app(TenantPricingService::class)->priceForRole($layanan, $role, $tenant, 1);
            $basePrice = (int) $price['amount'];

            // Minimal transaksi dibandingkan dengan harga SEBELUM diskon supaya
            // konsisten dengan validasi jalur order (OrderController@store).
            if ($voucher->mintrx && $voucher->mintrx > $basePrice) {
                return response()->json([
                    'status'  => false,
                    'title'   => 'Ooops!',
                    'message' => 'Minimal Transaksi > Rp ' . number_format($voucher->mintrx, 0, ',', '.'),
                ], 422);
            }

            $potongan = $basePrice * ($voucher->promo / 100);
            $maxPotongan = (float) ($voucher->max_potongan ?? 0);

            if($maxPotongan > 0 && $potongan > $maxPotongan){
                $potongan = $maxPotongan;
            }

            $hargaAkhir = (int) max(0, round($basePrice - $potongan));

            // Lantai harga: kupon tidak boleh membuat transaksi jatuh di bawah
            // harga beli tenant. Ditolak, BUKAN dinaikkan diam-diam — harga di
            // layar harus sama dengan harga yang ditagih. Di situs platform
            // `priceFloor()` mengembalikan null, jadi tidak ada perubahan
            // perilaku di sana.
            $floorViolation = app(TenantPricingService::class)
                ->floorViolation($layanan, $hargaAkhir, 1);

            if ($floorViolation !== null) {
                return response()->json([
                    'status'  => false,
                    'title'   => 'Ooops!',
                    'message' => $floorViolation,
                ], 422);
            }

            return response()->json([
                'status' => true,
                'message' => 'Voucher ditemukan',
                'harga' => $hargaAkhir
            ]);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'Voucher ditemukan'
        ]);
    }

    public function best(Request $request)
    {
        $request->validate([
            'service' => 'required|integer|exists:layanans,id',
        ]);

        $role = Auth::check() ? Auth::user()->role : 'Guest';

        $service = Layanan::query()->find($request->service);

        if ($service === null) {
            return response()->json(['status' => false, 'message' => 'Produk tidak ditemukan'], 404);
        }

        // Satu sumber harga, sama seperti `confirm()`. `best()` dulu memilih
        // kolom `harga_member`/`harga_gold`/`harga_platinum` langsung, jadi di
        // toko tenant `base_price` yang dikirim ke UI adalah harga platform —
        // daftar promo menampilkan potongan yang tidak akan pernah didapat
        // pembeli, dan urutannya pun salah karena selisihnya dihitung dari
        // angka yang bukan harga jual tenant.
        $tenant = app()->bound(\App\Tenancy\TenantContext::class)
            ? app(\App\Tenancy\TenantContext::class)->get()
            : null;

        $basePrice = (float) app(TenantPricingService::class)
            ->priceForRole($service, $role, $tenant, 1)['amount'];

        $vouchers = Voucher::query()
            ->where('stock', '>', 0)
            ->where(fn ($query) => $query
                ->whereNull('expired_at')
                ->orWhere('expired_at', '>=', now()))
            ->get()
            ->map(function (Voucher $voucher) use ($basePrice) {
                if ($voucher->mintrx && $basePrice < (float) $voucher->mintrx) {
                    return null;
                }

                $discount = $basePrice * ((float) $voucher->promo / 100);
                $maxDiscount = (float) $voucher->max_potongan;

                if ($maxDiscount > 0 && $discount > $maxDiscount) {
                    $discount = $maxDiscount;
                }

                $discount = max(0, (int) round($discount));
                $finalPrice = max(0, (int) round($basePrice - $discount));

                return [
                    'kode' => $voucher->kode,
                    'promo' => (float) $voucher->promo,
                    'stock' => (int) $voucher->stock,
                    'mintrx' => (int) ($voucher->mintrx ?? 0),
                    'max_potongan' => (int) ($voucher->max_potongan ?? 0),
                    'discount_amount' => $discount,
                    'final_price' => $finalPrice,
                ];
            })
            ->filter()
            ->sortByDesc('discount_amount')
            ->values();

        return response()->json([
            'status' => true,
            'message' => $vouchers->isEmpty() ? 'Tidak ada promo yang tersedia' : 'Promo tersedia',
            'base_price' => (int) round($basePrice),
            'vouchers' => $vouchers,
        ]);
    }
    
    public function show($id)
    {
        $data = Voucher::where('id', $id)->first();
        $expiredAt = $data->expired_at?->format('Y-m-d\\TH:i') ?? '';

        $send = "
                <form action='".route("voucher.detail.update", [$id])."' method='POST'>
                    <input type='hidden' name='_token' value='".csrf_token()."'>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Kode</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='".$data->kode. "' name='kode'>
                        </div>
                    </div>                     
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Potongan</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='".$data->promo. "' name='promo'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Stock</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='".$data->stock. "' name='stock'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Minimal Transaksi</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='".$data->mintrx. "' name='mintrx'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Max Potongan</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='".$data->max_potongan. "' name='max_potongan'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Expired</label>
                        <div class='col-lg-10'>
                            <input type='datetime-local' class='form-control' value='".$expiredAt. "' name='expired_at'>
                        </div>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
        ";

        return $send;          
    }
    
    public function patch(Request $request, $id)
    {
        $request->validate([
            'kode'  => 'required',
            'promo' => 'required|numeric|min:0|max:100',
            'stock' => 'required|numeric|min:0',
            'max_potongan' => 'required|numeric|min:0',
            'expired_at' => 'nullable|date',
            'layanan_id' => ['nullable', 'integer', 'exists:layanans,id'],
        ]);

        Voucher::where('id', $id)->update([
            'kode' => $request->kode,
            'promo' => $request->promo,
            'stock' => $request->stock,
            'mintrx' => $request->mintrx,
            'max_potongan' => $request->max_potongan,
            'expired_at' => $request->expired_at ?: null,
            'layanan_id' => $request->filled('layanan_id') ? (int) $request->layanan_id : null,
        ]);
        
        return back()->with('success', 'Berhasil update kode promo');
    }
}
