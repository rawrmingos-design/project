<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceDepositController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\OrderController as AdminOrder;
use App\Http\Controllers\Admin\Berita;
use App\Http\Controllers\CariController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\TokoPayCallbackController;
use App\Http\Controllers\PaydisiniCallbackController;
use App\Http\Controllers\DuitkuPaymentController;
use App\Http\Controllers\digiFlazzController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\DigiflazzCallbackController;
use App\Http\Controllers\RiwayatPembelian;
use App\Http\Controllers\Admin\UserDepositController;
use App\Http\Controllers\MemberController;
// use App\Http\Controllers\Admin\WhatsappController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\DeviceInfoController;
use App\Http\Controllers\PricelistController;
use App\Http\Controllers\DsController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\RecentPurchasesController;

use App\Http\Controllers\Admin\DataJokiController;
// use App\Http\Controllers\GiftskinController;
// use App\Http\Controllers\Admin\DataGiftSkinController;
use App\Http\Controllers\HitungpointmwController;
use App\Http\Controllers\CheckRegionController;
use App\Http\Controllers\HitungpointzodiacController;
use App\Http\Controllers\HitungwrController;
use App\Http\Controllers\ratingCustomerController;
use App\Http\Controllers\ratingAdminController;
use App\Http\Controllers\IPAddressController;
use App\Http\Controllers\PaketController;
use App\Http\Controllers\policyandtermss\TermsController;
use App\Http\Controllers\leaderboard\LeaderboardController;
use App\Http\Controllers\PaketLayananController;
use App\Http\Controllers\Admin\TabmenuController;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Controllers\Admin\BangjeffdashboardController;
use App\Http\Controllers\Admin\DigiflazzdashboardController;
use App\Http\Controllers\Admin\TopupediadashboardController;
use App\Http\Controllers\ApiCheckController;
use App\Http\Controllers\Admin\WhitelistedIPController;
use App\Models\PaketLayanan;
use App\Http\Controllers\TokoPayController;
use App\Http\Controllers\TriPayController;
use App\Http\Controllers\TriPayCallbackController;
use App\Http\Controllers\provider\MoogoldController;
use App\Http\Controllers\Ipay88Controller;
use App\Http\Controllers\SenangpayController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\ForgotPasswordController;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\ArtikelController;

Route::post('/senangpay/create', [SenangpayController::class, 'createPaymentRequest']);
Route::get('/senangpay/callback', [SenangpayController::class, 'handlePaymentResponse'])->name('senangpay.callback');

// Live Sales FOMO Toast - recent purchases for toast notifications
Route::get('/api/recent-purchases', [RecentPurchasesController::class, 'index']);

# CRONJOB
// /cronjob/update-gameshop
// /cronjob/update-strleyashop
Route::prefix('cronjob')->group(function () {
    Route::get('/update-gameshop', [\App\Http\Controllers\CronjobController::class, 'updateGameShop']);
    Route::get('/update-strleyashop', [\App\Http\Controllers\CronjobController::class, 'updateStrleyashop']);
    Route::get('/update-elitedias', [\App\Http\Controllers\CronjobController::class, 'updateElitedias']);
    Route::get('/update-yezzpay', [\App\Http\Controllers\CronjobController::class, 'updateYezzpay']);
});

Route::prefix('callback')->group(function () {
    Route::post('/razerpay', [\App\Http\Controllers\CallbackController::class, 'razerpay'])->name('callback.razerpay');
});


Route::get('language/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'id'])) {
        App::setLocale($lang);
        session(['locale' => $lang]);
    }
    return redirect()->back();
});


Route::get('/wip', function () {
    $ipAddress = request()->ip();
    return response()->json(['ip' => $ipAddress]);
});

Route::get('/api/recent-purchases', [\App\Http\Controllers\IndexController::class, 'recentPurchases']);

Route::get(
    '/weji-mt',
    function () {
        Illuminate\Support\Facades\Artisan::call('down', [
            '--secret' => 'kbrs-0189-kahisnxs',
        ]);

        dd(Artisan::output());
    }
);

Route::get(
    '/weji-up',
    function () {
        Artisan::call('up');
        dd(Artisan::output());
    }
);




Route::redirect('/', '/id');

Route::prefix('id')->middleware(['xss', 'sanitize'])->group(function () {

    // Artikel Routes
    Route::get('/artikel', [ArtikelController::class, 'index'])->name('artikel.index');
    Route::get('/artikel/{slug}', [ArtikelController::class, 'show'])->name('artikel.show');

    Route::get('/',                                                              [IndexController::class, 'create'])->name('home');

    Route::middleware(['auth', 'xss', 'sanitize'])->group(function () {
        Route::get('/dashboard',                                                     [DsController::class, 'dashboard'])->name('dashboard');
        Route::get('/settings',                                                      [DsController::class, 'editProfile'])->name('editProfile');
        Route::post('/settings',                                                     [DsController::class, 'saveEditProfile'])->name('saveEditProfile');
        Route::post('/id/logout',                                                    [LoginController::class, 'destroy'])->name('logout');
        Route::get('/deposit/history',                                              [DepositController::class, 'reloadd'])->name('reload');
        Route::get('/deposit',                                                      [DepositController::class, 'create'])->name('deposit');
        Route::post('/deposit',                                                     [DepositController::class, 'store'])->name('deposit.store');
        Route::get('/deposit/{order}',                                               [InvoiceDepositController::class, 'create'])->name('deposit.invoice');
        Route::get('/dashboard/history',                                             [RiwayatPembelian::class, 'create'])->name('riwayat');
        Route::get('/affiliate',                                                     [DsController::class, 'affiliate'])->name('affiliate');
        Route::get('/withdrawal',                                                    [DsController::class, 'withdrawal'])->name('withdrawal');
        Route::post('/withdrawal',                                                   [DsController::class, 'processWithdrawal'])->name('process.withdrawal');
    });

    // Rute publik
    Route::post('/cari/index',                                                   [IndexController::class, 'cariIndex']);
    Route::get('/invoices',                                                      [CariController::class, 'create'])->name('cari');
    Route::post('/cari',                                                         [CariController::class, 'store'])->name('cari.post');
    Route::get('/price-list',                                                    [PricelistController::class, 'create'])->name('price');
    Route::get('/calculator/magic-wheel',                                        [HitungpointmwController::class, 'create'])->name('hitungpointmw');
    Route::get('/cek-region',                                        [CheckRegionController::class, 'create'])->name('cekregion');
    Route::get('/calculator/zodiac',                                             [HitungpointzodiacController::class, 'create'])->name('hitungpointzodiac');
    Route::get('/calculator/winrate',                                            [HitungwrController::class, 'create'])->name('hitungwr');
    Route::get('/leaderboard',                                                   [LeaderboardController::class, 'leaderboard'])->name('leaderboardd');
    Route::get('/terms-and-condition',                                           [TermsController::class, 'terms'])->name('terms');
    Route::get('/privacy-policy',                                                [TermsController::class, 'policy'])->name('policy');
    Route::get('/policy',                                                [TermsController::class, 'privacy'])->name('privacy');
    Route::get('/sign-in',                                                       [LoginController::class, 'create'])->name('login');
    Route::post('/sign-in',                                                      [LoginController::class, 'store'])->name('post.login')->middleware('throttle:10,1');
    Route::get('/sign-up',                                                       [RegisterController::class, 'create'])->name('register');
    Route::post('/sign-up',                                                      [RegisterController::class, 'store'])->name('post.register');
    Route::get('/reviews',                                                       [RatingCustomerController::class, 'create'])->name('reviews');
    Route::get('/forgot-password',                                         [ForgotPasswordController::class, 'create'])->name('forgot');
    Route::post('/forgot-password',                                         [ForgotPasswordController::class, 'store'])->name('post.forgot');
    Route::get('/docs', [OrderApiController::class, 'documentation'])->name('docs');
});

Route::get('/wlip',                                                        [WhitelistedIPController::class, 'index'])->name('whitelisted-ips.index');
Route::delete('/wlip/{whitelistedIP}',                                     [WhitelistedIPController::class, 'destroy'])->name('whitelisted-ips.destroy');
Route::get('/wlip/create',                                                 [WhitelistedIPController::class, 'create'])->name('whitelisted-ips.create');
Route::post('/wlip',                                                       [WhitelistedIPController::class, 'store'])->name('whitelisted-ips.store');

Route::middleware(['xss', 'sanitize',])->group(function () {
    Route::get('/id/{kategori:kode}',                                            [OrderController::class, 'create']);
    Route::post('/id/harga',                                                     [OrderController::class, 'price'])->name('ajax.price');
    Route::post('/id/konfirmasi-data',          [OrderController::class, 'confirm'])->name('ajax.confirmation');
    Route::post('/ajax/check-account',          [OrderController::class, 'checkAccount'])->name('ajax.check-account');
    Route::post('/id',                                                           [OrderController::class, 'store'])->name('ordered');
    Route::get('/id/invoices/{order}',                                           [InvoiceController::class, 'create'])->name('pembelian');
    Route::post('/id/invoices/{order}',                                          [InvoiceController::class, 'ratingCustomer'])->name('rating.pembelian');
    Route::get('/ajax/transaction-status/{order}',                               [InvoiceController::class, 'checkStatus'])->name('ajax.status');
    Route::get('/ajax/deposit-status/{order}',                                   [InvoiceDepositController::class, 'checkStatus'])->name('ajax.deposit-status');
    Route::post('/check-voucher',                                                [VoucherController::class, 'confirm'])->name('check.voucher');
});

// Rute callback
Route::post('/wejizy/digi/payload',                                                   [DigiflazzCallbackController::class, 'handle']);
Route::post('/wejizy/tokopay/callback',                                              [TokoPayCallbackController::class, 'handle']);
Route::post('/wejizy/tripay/callback', [TriPayCallbackController::class, 'handle']);
Route::post('/wejizy/paydisini/callback', [PaydisiniCallbackController::class, 'callbackTransaction']);
Route::post('/wejizy/duitku/callback', [DuitkuPaymentController::class, 'handleCallback'])->name('duitku.callback');
Route::post('/ipay88/callback', [IPay88Controller::class, 'paymentResponse'])->name('ipay88.callback');
Route::post('/ipay88/backend', [IPay88Controller::class, 'backendResponse'])->name('ipay88.backend');

Route::middleware(['auth', 'check.role'])->group(function () {
    Route::get('/dashboard',                                                     [DashboardController::class, 'create'])->name('dashboard.legacy');
    Route::get('/pesanan',                                                       [AdminOrder::class, 'create'])->name('pesanan');
    Route::get('/order-status/{order_id}/{status}',                              [AdminOrder::class, 'update']);
    Route::get('/process-order/{order_id}',                              [AdminOrder::class, 'reorder']);


    Route::prefix('bangjeff')->middleware(['auth', 'check.role'])->group(function () {
        Route::get('/balance',                                                       [BangjeffdashboardController::class, 'balance'])->name('bangjeff.balance');
        Route::get('/product',                                                       [BangjeffdashboardController::class, 'getProduct'])->name('bangjeff.product');
    });
    Route::prefix('topupedia')->middleware(['auth', 'check.role'])->group(function () {
        Route::get('/balance',                                                       [TopupediadashboardController::class, 'balance'])->name('topupedia.balance');
        Route::get('/product',                                                       [TopupediadashboardController::class, 'getProduct'])->name('topupedia.product');
    });

    Route::prefix('digiflazz')->middleware(['auth', 'check.role'])->group(function () {
        Route::get('/produk',                                                        [DigiflazzdashboardController::class, 'harga'])->name('digiflazz.prices');
    });

    Route::get('/berita',                                                        [Berita::class, 'create'])->name('berita');
    Route::post('/berita',                                                       [Berita::class, 'post'])->name('berita.post');
    Route::get('/berita/hapus/{id}',                                             [Berita::class, 'delete'])->name('berita.delete');


    Route::get('/tarik-saldo',                                                   [TokoPayController::class, 'tarikSaldo']);
    Route::post('/tarik-saldo',                                                  [TokoPayController::class, 'tarikSaldo'])->name('tarik-saldo');
    Route::get('/informasi-akun',                                                [TokoPayController::class, 'akun'])->name('informasi-akun');
    Route::get('/status-order',                                                  [TokoPayController::class, 'cekStatusOrder'])->name('status-order');
    Route::get('/withdrawals', function () {
        $withdrawals = Withdrawal::all();
        return view('withdrawals', ['withdrawals' => $withdrawals]);
    });
    Route::get('/kategori',                                                      [KategoriController::class, 'create'])->name('kategori');
    Route::post('/kategori',                                                     [KategoriController::class, 'store'])->name('kategori.post');
    Route::get('/kategori/hapus/{id}',                                           [KategoriController::class, 'delete'])->name('kategori.delete');
    Route::get('/kategori-status/{id}/{status}',                                 [KategoriController::class, 'update'])->name('kategori.update');

    Route::get('/kategori/{id}/detail',                                          [KategoriController::class, 'detail'])->name('kategori.detail');
    Route::post('/kategori/{id}/detail',                                         [KategoriController::class, 'patch'])->name('kategori.detail.update');
    Route::get('/produk/get/{provider?}',                                        [ProdukController::class, 'get'])->name('produk.get');
    Route::post('/produk/get/{provider?}',                                       [ProdukController::class, 'store'])->name('produk.get.post');
    Route::post('/produk/sync/',                                                 [ProdukController::class, 'sync'])->name('sync.produk.get.post');
    Route::post('/produk/syncmoogold/',                                          [ProdukController::class, 'syncmoogold'])->name('syncmoogold.produk.get.post');
    Route::post('/produk/synctopupedia/',                                        [ProdukController::class, 'synctopupedia'])->name('synctopupedia.produk.get.post');
    Route::get('/produk/get/{id}/detail',                                        [ProdukController::class, 'detail'])->name('detail.produk.get');
    Route::post('/produk/get/{id}/detail',                                       [ProdukController::class, 'patch'])->name('detail.produk.get.update');
    Route::get('/rating-customer',                                               [ratingAdminController::class, 'create'])->name('rating-customer');
    Route::delete('/rating-customer/{id}',                                       [ratingAdminController::class, 'destroy'])->name('rating-customer.destroy');
    Route::get('/layanan',                                                       [LayananController::class, 'create'])->name('layanan');
    Route::post('/layanan',                                                      [LayananController::class, 'store'])->name('layanan.post');
    Route::get('/layanan/hapus/{id}',                                            [LayananController::class, 'delete'])->name('layanan.delete');
    Route::get('/layanan-status/{id}/{status}',                                  [LayananController::class, 'update'])->name('layanan.update');
    Route::get('/layanan/{id}/detail',                                           [LayananController::class, 'detail'])->name('layanan.detail');
    Route::post('/layanan/{id}/detail',                                          [LayananController::class, 'patch'])->name('layanan.detail.update');
    Route::post('/layanan/bulk-delete',                                          [LayananController::class, 'bulkDelete'])->name('layanan.bulkDelete');

    Route::resources(['paket' => PaketController::class, 'paket-layanan' => PaketLayananController::class]);
    Route::post('paket-layanan-get-layanan',                                     [PaketLayananController::class, 'get_layanan'])->name('paket-layanan.get-layanan');
    Route::delete('/paket-layanan/destroy',                                      [PaketLayananController::class, 'destroy'])->name('paket-layanan.destroy.custom');
    Route::get('/method', [MethodController::class, 'create'])->name('method');
    Route::post('/method', [MethodController::class, 'store'])->name('method.post');
    Route::get('/method/hapus/{id}', [MethodController::class, 'delete'])->name('method.delete');
    Route::get('/method/{id}/detail', [MethodController::class, 'detail'])->name('method.detail');
    Route::post('/method/{id}/detail', [MethodController::class, 'patch'])->name('method.detail.update');
    Route::patch('/method/toggle-status/{id}', [MethodController::class, 'toggleStatus'])->name('method.toggleStatus');
    Route::get('/member',                                                        [MemberController::class, 'create'])->name('member');
    Route::get('/member/{id}/delete',                                            [MemberController::class, 'delete'])->name('member.delete');
    Route::post('/member',                                                       [MemberController::class, 'store'])->name('member.post');
    Route::post('/member/update/{id}', [MemberController::class, 'patch'])->name('member.detail.update');

    Route::post('/send-balance',                                                 [MemberController::class, 'send'])->name('saldo.post');
    Route::get('/member/{id}/detail',                                            [MemberController::class, 'show'])->name('member.detail');

    Route::get('/user-deposit',                                                  [UserDepositController::class, 'create'])->name('userdeposit');
    Route::get('/user-deposit/{id}/{status}',                                    [UserDepositController::class, 'patch'])->name('confirm.deposit');

    Route::get('/voucher',                                                       [VoucherController::class, 'create'])->name('voucher');
    Route::post('/voucher',                                                      [VoucherController::class, 'store'])->name('voucher.post');
    Route::get('/voucher/{id}/delete',                                           [VoucherController::class, 'destroy'])->name('voucher.delete');
    Route::get('/voucher/{id}/detail',                                           [VoucherController::class, 'show'])->name('voucher.detail');
    Route::post('/voucher/{id}/update',                                          [VoucherController::class, 'patch'])->name('voucher.detail.update');
    Route::get('/data/joki',                                                     [DataJokiController::class, 'dataJoki']);
    Route::get('/joki-status/{order_id}/{status}',                               [DataJokiController::class, 'statusJoki']);
    Route::get('/joki/hapus/{id}',                                               [DataJokiController::class, 'hapusJoki']);
});
