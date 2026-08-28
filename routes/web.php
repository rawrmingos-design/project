<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceDepositController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrder;
use App\Http\Controllers\Admin\Berita;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\TokoPayCallbackController;
use App\Http\Controllers\PaydisiniCallbackController;
use App\Http\Controllers\DuitkuPaymentController;
use App\Http\Controllers\Api\SubscriptionWebhookController;
use App\Http\Controllers\DigiflazzCallbackController;
use App\Http\Controllers\VipResellerCallbackController;
use App\Http\Controllers\ApiGamesCallbackController;
use App\Http\Controllers\Admin\UserDepositController;
use App\Http\Controllers\MemberController;
// use App\Http\Controllers\Admin\WhatsappController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\DsController;
use App\Http\Controllers\MethodController;

use App\Http\Controllers\Admin\DataJokiController;
// use App\Http\Controllers\GiftskinController;
// use App\Http\Controllers\Admin\DataGiftSkinController;
use App\Http\Controllers\ratingAdminController;
use App\Http\Controllers\PaketController;
use App\Http\Controllers\PaketLayananController;
use App\Http\Controllers\Admin\BangjeffdashboardController;
use App\Http\Controllers\Admin\DigiflazzdashboardController;
use App\Http\Controllers\Admin\TopupediadashboardController;
use App\Http\Controllers\TokoPayController;
use App\Http\Controllers\TriPayCallbackController;
use App\Http\Controllers\SenangpayController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\PwaManifestController;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Public\HomeController as PublicHomeController;
use App\Http\Controllers\Public\OrderPageController as PublicOrderPageController;
use App\Http\Controllers\Public\InvoicePageController as PublicInvoicePageController;
use App\Http\Controllers\Public\LeaderboardPageController as PublicLeaderboardPageController;
use App\Http\Controllers\Public\CalculatorPageController as PublicCalculatorPageController;
use App\Http\Controllers\Public\ProductSearchController as PublicProductSearchController;
use App\Http\Controllers\Public\TransactionLookupPageController as PublicTransactionLookupPageController;
use App\Http\Controllers\Public\ArticlePageController as PublicArticlePageController;
use App\Http\Controllers\Public\DashboardPageController as PublicDashboardPageController;
use App\Http\Controllers\Public\SettingsPageController as PublicSettingsPageController;
use App\Http\Controllers\Public\TransactionHistoryPageController as PublicTransactionHistoryPageController;
use App\Http\Controllers\Public\DepositHistoryPageController as PublicDepositHistoryPageController;
use App\Http\Controllers\Public\DepositInvoicePageController as PublicDepositInvoicePageController;
use App\Http\Controllers\Public\DepositPageController as PublicDepositPageController;
use App\Http\Controllers\Public\AffiliatePageController as PublicAffiliatePageController;
use App\Http\Controllers\Public\AffiliateWithdrawalPageController as PublicAffiliateWithdrawalPageController;
use App\Http\Controllers\Public\LegalPageController as PublicLegalPageController;
use App\Http\Controllers\Public\InformationalPageController as PublicInformationalPageController;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\HomeController as TenantHomeController;
use App\Http\Controllers\Tenant\RegistrationPageController as TenantRegistrationPageController;
use App\Http\Controllers\Tenant\SettingsController as TenantSettingsController;
use App\Http\Controllers\GoogleAuthController;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/site.webmanifest', PwaManifestController::class)->name('pwa.manifest');
Route::get('/.well-known/assetlinks.json', \App\Http\Controllers\DigitalAssetLinksController::class)
    ->name('digital.asset.links');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/sitemap-main.xml', [SeoController::class, 'sitemapMain'])->name('seo.sitemap.main');
Route::get('/sitemap-categories.xml', [SeoController::class, 'sitemapCategories'])->name('seo.sitemap.categories');
Route::redirect('/id/sitemap.xml', '/sitemap.xml', 301);
Route::redirect('/id/sitemap-main.xml', '/sitemap-main.xml', 301);
Route::redirect('/id/sitemap-categories.xml', '/sitemap-categories.xml', 301);

Route::post('/senangpay/create', [SenangpayController::class, 'createPaymentRequest']);
Route::get('/senangpay/callback', [SenangpayController::class, 'handlePaymentResponse'])->name('senangpay.callback');

Route::prefix('callback')->group(function () {
    Route::post('/razerpay', [\App\Http\Controllers\CallbackController::class, 'razerpay'])
        ->middleware('throttle:razer-callback')
        ->name('callback.razerpay');
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

$publicHost = parse_url((string) config('app.url'), PHP_URL_HOST);
$adminHostRaw = trim((string) config('app.filament_admin_domain', ''));
$adminHost = $adminHostRaw;

if ($adminHost !== '' && str_contains($adminHost, '://')) {
    $adminHost = (string) (parse_url($adminHost, PHP_URL_HOST) ?? '');
}

$adminHost = preg_replace('/:\d+$/', '', $adminHost) ?? '';

$docsHostRaw = trim((string) env('DOCS_DOMAIN', ''));
$docsHost = $docsHostRaw;
if ($docsHost !== '' && str_contains($docsHost, '://')) {
    $docsHost = (string) (parse_url($docsHost, PHP_URL_HOST) ?? '');
}
$docsHost = preg_replace('/:\d+$/', '', $docsHost) ?? '';

if (is_string($publicHost) && $publicHost !== '') {
    Route::domain($publicHost)->group(function () {
        Route::redirect('/', '/id', 301);
        Route::middleware(['auth', 'check.role'])->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'create'])->name('dashboard.legacy');
        });
    });
} else {
    Route::middleware(['auth', 'check.role'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'create'])->name('dashboard.legacy');
    });
}


if ($adminHost !== '') {
    Route::domain($adminHost)->group(function () {
        Route::redirect('/id', '/login', 302);
        Route::redirect('/id/{any}', '/login', 302)->where('any', '.*');
    });
}

if ($docsHost !== '') {
    Route::domain($docsHost)->middleware(['xss', 'sanitize'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Public\DocsController::class, 'index'])
            ->name('docs.index')
            ->middleware('auth.message:Anda harus login terlebih dahulu untuk mengakses dokumentasi API.');
    });
}

Route::middleware(['tenant.resolve', 'tenant.required', 'xss', 'sanitize'])->name('tenant.')->group(function () {
    Route::get('/', TenantHomeController::class)->name('home');
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', TenantDashboardController::class)->name('dashboard');
        Route::get('/settings', [TenantSettingsController::class, 'edit'])->name('settings');
        Route::post('/settings', [TenantSettingsController::class, 'update'])->name('settings.update');
    });
    Route::get('/order/{kategori:kode}', PublicOrderPageController::class)
        ->missing(fn () => redirect('/', 302))
        ->name('order');
    Route::post('/order/price', [OrderController::class, 'price'])->middleware('throttle:public-order-price')->name('order.price');
    Route::post('/order/checkout', [OrderController::class, 'store'])->middleware('throttle:public-order-submit')->name('order.checkout');
    Route::post('/order/confirm', [OrderController::class, 'confirm'])->middleware('throttle:public-order-confirm')->name('order.confirm');
    Route::get('/invoices/{order}', PublicInvoicePageController::class)->middleware('throttle:public-status')->name('invoice');
    Route::get('/track/{order}', PublicInvoicePageController::class)->middleware('throttle:public-status')->name('track');
});

Route::prefix('id')->group(function () {
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:password-recovery-request')
        ->name('post.forgot');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])
        ->middleware('throttle:password-reset-submit')
        ->name('password.update');
});

Route::prefix('id')->middleware(['xss', 'sanitize', 'bangjeff.legacy.redirect'])->group(function () {

    // Artikel Routes (Inertia for bangjeff theme, blade fallback in controller for default theme)
    Route::prefix('artikel')->name('artikel.')->controller(PublicArticlePageController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{slug}', 'show')->where('slug', '[A-Za-z0-9\-]+')->name('show');
    });

    Route::get('/',                                                              PublicHomeController::class)->name('home');
    Route::get('/reseller-topup',                                                TenantRegistrationPageController::class)->name('tenant.register.page');

    Route::middleware(['auth', 'xss', 'sanitize'])->group(function () {
        Route::get('/dashboard', PublicDashboardPageController::class)->middleware(['non-admin.public-dashboard', 'reseller.redirect'])->name('dashboard');
        Route::get('/settings',                                                      [PublicSettingsPageController::class, 'index'])->name('editProfile');
        Route::post('/settings',                                                     [PublicSettingsPageController::class, 'updateProfile'])->name('saveEditProfile');
        Route::get('/settings/whatsapp/status',                                      [PublicSettingsPageController::class, 'whatsappLinkStatus'])->middleware('throttle:security-settings')->name('settings.whatsapp.status');
        Route::post('/settings/whatsapp/link',                                      [PublicSettingsPageController::class, 'createWhatsappLinkChallenge'])->middleware('throttle:security-settings')->name('settings.whatsapp.link');
        Route::post('/settings/whatsapp/revoke',                                    [PublicSettingsPageController::class, 'revokeWhatsappLinkChallenge'])->middleware('throttle:security-settings')->name('settings.whatsapp.revoke');
        Route::post('/settings/whatsapp/unlink',                                    [PublicSettingsPageController::class, 'unlinkWhatsapp'])->middleware('throttle:critical-security-settings')->name('settings.whatsapp.unlink');
        Route::get('/settings/telegram/status',                                     [PublicSettingsPageController::class, 'telegramLinkStatus'])->middleware('throttle:security-settings')->name('settings.telegram.status');
        Route::post('/settings/telegram/link',                                     [PublicSettingsPageController::class, 'createTelegramLinkChallenge'])->middleware('throttle:security-settings')->name('settings.telegram.link');
        Route::post('/settings/telegram/revoke',                                   [PublicSettingsPageController::class, 'revokeTelegramLinkChallenge'])->middleware('throttle:security-settings')->name('settings.telegram.revoke');
        Route::post('/settings/telegram/unlink',                                   [PublicSettingsPageController::class, 'unlinkTelegram'])->middleware('throttle:critical-security-settings')->name('settings.telegram.unlink');
        Route::post('/settings/change-password',                                     [PublicSettingsPageController::class, 'changePassword'])->middleware('throttle:security-settings')->name('settings.change-password');
        Route::post('/settings/api-key/regenerate',                                  [PublicSettingsPageController::class, 'regenerateApiKey'])->middleware(['not-reseller', 'throttle:critical-security-settings'])->name('settings.api-key.regenerate');
        Route::post('/settings/2fa/setup',                                           [PublicSettingsPageController::class, 'setupTwoFactor'])->middleware('throttle:security-settings')->name('settings.2fa.setup');
        Route::post('/settings/2fa/enable',                                          [PublicSettingsPageController::class, 'enableTwoFactor'])->middleware('throttle:security-settings')->name('settings.2fa.enable');
        Route::post('/settings/2fa/disable',                                         [PublicSettingsPageController::class, 'disableTwoFactor'])->middleware('throttle:critical-security-settings')->name('settings.2fa.disable');
        Route::post('/logout',                                                       [LoginController::class, 'destroy'])->name('logout');
        Route::post('/id/logout',                                                    [LoginController::class, 'destroy'])->name('logout.legacy');
        Route::get('/deposit/history',                                               PublicDepositHistoryPageController::class)->middleware('not-reseller:reseller.deposits')->name('reload');
        Route::get('/deposit',                                                      PublicDepositPageController::class)->middleware('non-affiliate.only')->name('deposit');
        Route::post('/deposit',                                                     [DepositController::class, 'store'])->middleware(['non-affiliate.only', 'throttle:public-deposit-submit'])->name('deposit.store');
        Route::get('/deposit/{order}',                                               PublicDepositInvoicePageController::class)->name('deposit.invoice');
        Route::get('/dashboard/history',                                             PublicTransactionHistoryPageController::class)->middleware('not-reseller')->name('riwayat');
        Route::get('/affiliate',                                                     PublicAffiliatePageController::class)->name('affiliate');
        Route::post('/affiliate/request',                                            PublicAffiliatePageController::class)->middleware('throttle:public-affiliate-request')->name('affiliate.request');
        Route::get('/withdrawal',                                                    PublicAffiliateWithdrawalPageController::class)->middleware('affiliate.only')->name('withdrawal');
        Route::post('/withdrawal',                                                   [DsController::class, 'processWithdrawal'])->middleware(['affiliate.only', 'throttle:public-withdrawal-submit'])->name('process.withdrawal');

        // Reseller Panel MVP Routes
        Route::prefix('reseller')->name('reseller.')->middleware(['reseller.only'])->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Public\Reseller\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/settings', [\App\Http\Controllers\Public\Reseller\SettingsController::class, 'index'])->name('settings');
            Route::get('/deposit-methods', [\App\Http\Controllers\Public\Reseller\DepositMethodController::class, 'index'])->name('deposit.methods');
            Route::get('/credentials', [\App\Http\Controllers\Public\Reseller\CredentialController::class, 'index'])->name('credentials');
            Route::get('/docs', function () {
                $docsUrl = app(\App\Services\PublicSiteConfigService::class)->docsUrl();

                abort_if($docsUrl === null, 404);

                return redirect()->away($docsUrl, 301);
            })->name('docs');
            Route::post('/credentials/rotate-live', [\App\Http\Controllers\Public\Reseller\RotateKeyController::class, 'rotateLive'])->middleware('throttle:reseller-credential-mutation')->name('credentials.rotate.live');
            Route::post('/credentials/rotate-sandbox', [\App\Http\Controllers\Public\Reseller\RotateKeyController::class, 'rotateSandbox'])->middleware('throttle:reseller-credential-mutation')->name('credentials.rotate.sandbox');
            Route::post('/credentials/webhook', [\App\Http\Controllers\Public\Reseller\CredentialController::class, 'updateWebhook'])->middleware('throttle:reseller-credential-mutation')->name('credentials.webhook.update');
            Route::post('/ip-whitelist', [\App\Http\Controllers\Public\Reseller\IpWhitelistController::class, 'store'])->middleware('throttle:reseller-credential-mutation')->name('ip.whitelist.store');
            Route::delete('/ip-whitelist/{ip}', [\App\Http\Controllers\Public\Reseller\IpWhitelistController::class, 'destroy'])->middleware('throttle:reseller-credential-mutation')->where('ip', '.*')->name('ip.whitelist.destroy');
            Route::get('/callbacks', [\App\Http\Controllers\Public\Reseller\CallbackLogController::class, 'index'])->name('callbacks');
            Route::post('/callbacks/{delivery}/resend', [\App\Http\Controllers\Public\Reseller\CallbackLogController::class, 'resend'])
                ->middleware('throttle:reseller-callback-resend')
                ->name('callbacks.resend');
            // Phase 5 — Task 5.3: Sandbox webhook tester
            Route::post('/callbacks/test', [\App\Http\Controllers\Public\Reseller\CallbackTestController::class, 'fire'])
                ->middleware('throttle:reseller-callback-test')
                ->name('callbacks.test');

            Route::get('/orders',    [\App\Http\Controllers\Public\Reseller\OrderLogController::class,        'index'])->name('orders');
            Route::get('/deposits',  [\App\Http\Controllers\Public\Reseller\DepositHistoryController::class,  'index'])->name('deposits');
            Route::get('/sandbox',   [\App\Http\Controllers\Public\Reseller\SandboxController::class,          'index'])->name('sandbox');
            Route::post('/sandbox/simulate', [\App\Http\Controllers\Public\Reseller\SandboxController::class,  'simulateStatus'])->middleware('throttle:reseller-sandbox-mutation')->name('sandbox.simulate');
            
            // Notifications API
            Route::get('/notifications', [\App\Http\Controllers\Public\Reseller\NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/notifications/unread-count', [\App\Http\Controllers\Public\Reseller\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
            Route::post('/notifications/read-all', [\App\Http\Controllers\Public\Reseller\NotificationController::class, 'markAllAsRead'])->middleware('throttle:reseller-notification-mutation')->name('notifications.read-all');
            Route::post('/notifications/{id}/read', [\App\Http\Controllers\Public\Reseller\NotificationController::class, 'markAsRead'])->middleware('throttle:reseller-notification-mutation')->name('notifications.read');

            // Native web push (reseller-only, manual test phase)
            Route::post('/push-subscriptions', [\App\Http\Controllers\Public\Reseller\PushSubscriptionController::class, 'store'])->middleware('throttle:reseller-notification-mutation')->name('push-subscriptions.store');
            Route::delete('/push-subscriptions', [\App\Http\Controllers\Public\Reseller\PushSubscriptionController::class, 'destroy'])->middleware('throttle:reseller-notification-mutation')->name('push-subscriptions.destroy');
            Route::post('/push-subscriptions/test', [\App\Http\Controllers\Public\Reseller\PushSubscriptionController::class, 'sendTest'])
                ->middleware('throttle:reseller-callback-test')
                ->name('push-subscriptions.test');
        });


    });

    // Reseller Registry (Application Form) - Public viewing, auth required for submit
    Route::get('/reseller/registry', [\App\Http\Controllers\Public\Reseller\RegistryController::class, 'showForm'])
        ->middleware(['xss', 'sanitize'])
        ->name('reseller.registry.form');
    Route::post('/reseller/registry', [\App\Http\Controllers\Public\Reseller\RegistryController::class, 'submit'])
        ->middleware(['xss', 'sanitize', 'auth', 'throttle:5,1'])
        ->name('reseller.registry.submit');

    // Reseller Sales Page (Public - no auth required)
    Route::get('/reseller', [\App\Http\Controllers\Public\Reseller\SalesPageController::class, '__invoke'])
        ->name('reseller.sales');

    // Rute publik
    Route::post('/cari/index',                                                   [IndexController::class, 'cariIndex'])->middleware('throttle:public-search');
    Route::get('/search/products',                                               [PublicProductSearchController::class, 'index'])->middleware('throttle:public-search')->name('public.search.products');
    Route::get('/invoices',                                                      [PublicTransactionLookupPageController::class, 'index'])->middleware('throttle:public-api-read')->name('cari');
    Route::post('/cari',                                                         [PublicTransactionLookupPageController::class, 'lookup'])->middleware('throttle:public-transaction-lookup')->name('cari.post');
    Route::get('/price-list',                                                    [PublicInformationalPageController::class, 'priceList'])->middleware('throttle:public-api-expensive-read')->name('price');
    Route::get('/calculator/magic-wheel',                                        [PublicCalculatorPageController::class, 'magicWheel'])->name('hitungpointmw');
    Route::get('/calculator/zodiac',                                             [PublicCalculatorPageController::class, 'zodiac'])->name('hitungpointzodiac');
    Route::get('/calculator/winrate',                                            [PublicCalculatorPageController::class, 'winrate'])->name('hitungwr');
    Route::get('/leaderboard',                                                   PublicLeaderboardPageController::class)->middleware('throttle:public-api-expensive-read')->name('leaderboardd');
    Route::get('/terms-and-condition',                                           [PublicLegalPageController::class, 'terms'])->name('terms');
    Route::get('/privacy-policy',                                                [PublicLegalPageController::class, 'privacyPolicy'])->name('policy');
    Route::get('/account-deletion',                                              [PublicLegalPageController::class, 'accountDeletion'])->name('account.deletion');
    Route::get('/policy',                                                        [PublicLegalPageController::class, 'privacy'])->name('privacy');
    Route::get('/affiliate/program-terms',                                       [PublicLegalPageController::class, 'affiliateProgramTerms'])->name('affiliate.program.terms');
    Route::get('/sign-in',                                                       [LoginController::class, 'create'])->name('login');
    Route::post('/sign-in',                                                      [LoginController::class, 'store'])->name('post.login')->middleware('throttle:public-login');
    Route::get('/sign-up',                                                       [RegisterController::class, 'create'])->name('register');
    Route::post('/sign-up',                                                      [RegisterController::class, 'store'])->name('post.register')->middleware('throttle:public-register');
    Route::post('/auth/google',                                                  [GoogleAuthController::class, 'store'])->name('auth.google')->middleware('throttle:public-login');
    Route::get('/reviews',                                                       [PublicInformationalPageController::class, 'reviews'])->middleware('throttle:public-api-expensive-read')->name('reviews');
    Route::get('/forgot-password',                                         [PublicInformationalPageController::class, 'forgotPassword'])->name('forgot');
});

Route::middleware(['xss', 'sanitize', 'bangjeff.legacy.redirect'])->group(function () {
    Route::post('/id/push-subscriptions', [\App\Http\Controllers\Public\PushSubscriptionController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('pwa.push-subscriptions.store');
    Route::delete('/id/push-subscriptions', [\App\Http\Controllers\Public\PushSubscriptionController::class, 'destroy'])
        ->middleware('throttle:20,1')
        ->name('pwa.push-subscriptions.destroy');

    Route::get('/id/{kategori:kode}',                                            PublicOrderPageController::class)
        ->missing(fn () => redirect('/id', 302));
    Route::post('/id/harga',                                                     [OrderController::class, 'price'])->middleware('throttle:public-order-price')->name('ajax.price');
    Route::post('/id/konfirmasi-data',          [OrderController::class, 'confirm'])->middleware('throttle:public-order-confirm')->name('ajax.confirmation');
    Route::post('/ajax/check-account',          [OrderController::class, 'checkAccount'])->middleware('throttle:public-account-check')->name('ajax.check-account');
    Route::post('/id',                                                           [OrderController::class, 'store'])->middleware('throttle:public-order-submit')->name('ordered');
    Route::get('/id/invoices/{order}',                                           PublicInvoicePageController::class)->middleware('throttle:public-status')->name('pembelian');
    Route::post('/id/invoices/{order}',                                          [InvoiceController::class, 'ratingCustomer'])->middleware('throttle:public-order-confirm')->name('rating.pembelian');
    Route::get('/ajax/transaction-status/{order}',                               [InvoiceController::class, 'checkStatus'])->middleware('throttle:public-status')->name('ajax.status');
    Route::get('/ajax/deposit-status/{order}',                                   [InvoiceDepositController::class, 'checkStatus'])->middleware('throttle:public-status')->name('ajax.deposit-status');
    Route::post('/check-voucher',                                                [VoucherController::class, 'confirm'])->middleware('throttle:public-voucher')->name('check.voucher');
    Route::post('/available-voucher',                                            [VoucherController::class, 'best'])->middleware('throttle:public-voucher')->name('available.voucher');
});

// Rute callback
Route::post('/wejizy/digi/payload', [DigiflazzCallbackController::class, 'handle'])
    ->middleware(['throttle:supplier-callback', 'inbound.whitelist:supplier_callback,digiflazz,log_only']);
Route::post('/wejizy/vip/callback', [VipResellerCallbackController::class, 'handle'])
    ->middleware(['throttle:supplier-callback', 'inbound.whitelist:supplier_callback,vip,log_only']);
Route::match(['get', 'post'], '/wejizy/apigames/callback', [ApiGamesCallbackController::class, 'handle'])
    ->middleware(['throttle:supplier-callback', 'inbound.whitelist:supplier_callback,apigames,log_only']);
Route::post('/wejizy/tokopay/callback', [TokoPayCallbackController::class, 'handle'])
    ->middleware(['throttle:payment-callback', 'inbound.whitelist:payment_gateway,tokopay,log_only']);
Route::post('/wejizy/tripay/callback', [TriPayCallbackController::class, 'handle'])
    ->middleware(['throttle:payment-callback', 'inbound.whitelist:payment_gateway,tripay,log_only']);
Route::post('/wejizy/paydisini/callback', [PaydisiniCallbackController::class, 'callbackTransaction'])
    ->middleware(['throttle:payment-callback', 'inbound.whitelist:payment_gateway,paydisini,log_only']);
Route::post('/wejizy/duitku/callback', [DuitkuPaymentController::class, 'handleCallback'])
    ->middleware(['throttle:payment-callback', 'inbound.whitelist:payment_gateway,duitku,log_only'])
    ->name('duitku.callback');
Route::post('/wejizy/duitku/subscription/callback', [SubscriptionWebhookController::class, 'duitku'])
    ->middleware(['throttle:subscription-callback', 'inbound.whitelist:payment_gateway,duitku,log_only'])
    ->name('duitku.subscription.callback');

Route::middleware(['auth', 'check.role'])->group(function () {
    Route::get('/pesanan',                                                       [AdminOrder::class, 'create'])->name('pesanan');
    Route::get('/order-status/{order_id}/{status}',                              [AdminOrder::class, 'update'])->middleware('throttle:admin-financial-mutation');
    Route::get('/process-order/{order_id}',                              [AdminOrder::class, 'reorder'])->middleware('throttle:admin-order-retry');


    Route::prefix('bangjeff')->middleware(['auth', 'check.role'])->group(function () {
        Route::get('/balance',                                                       [BangjeffdashboardController::class, 'balance'])->middleware('throttle:admin-external-read')->name('bangjeff.balance');
        Route::get('/product',                                                       [BangjeffdashboardController::class, 'getProduct'])->middleware('throttle:admin-external-read')->name('bangjeff.product');
    });
    Route::prefix('topupedia')->middleware(['auth', 'check.role'])->group(function () {
        Route::get('/balance',                                                       [TopupediadashboardController::class, 'balance'])->middleware('throttle:admin-external-read')->name('topupedia.balance');
        Route::get('/product',                                                       [TopupediadashboardController::class, 'getProduct'])->middleware('throttle:admin-external-read')->name('topupedia.product');
    });

    Route::prefix('digiflazz')->middleware(['auth', 'check.role'])->group(function () {
        Route::get('/produk',                                                        [DigiflazzdashboardController::class, 'harga'])->middleware('throttle:admin-external-read')->name('digiflazz.prices');
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
    Route::post('/produk/sync/',                                                 [ProdukController::class, 'sync'])->middleware('throttle:admin-provider-sync')->name('sync.produk.get.post');
    Route::post('/produk/syncmoogold/',                                          [ProdukController::class, 'syncmoogold'])->middleware('throttle:admin-provider-sync')->name('syncmoogold.produk.get.post');
    Route::post('/produk/synctopupedia/',                                        [ProdukController::class, 'synctopupedia'])->middleware('throttle:admin-provider-sync')->name('synctopupedia.produk.get.post');
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
    Route::post('/layanan/bulk-delete',                                          [LayananController::class, 'bulkDelete'])->middleware('throttle:admin-bulk-mutation')->name('layanan.bulkDelete');

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

Route::fallback(function (\Illuminate\Http\Request $request) {
    if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
        abort(404);
    }

    // Read lazily at request time so test suite app-instance overrides (putenv) are respected
    $lazyPublicHost  = parse_url((string) config('app.url'), PHP_URL_HOST);
    $lazyAdminRaw    = trim((string) config('app.filament_admin_domain', ''));
    $lazyAdminHost   = $lazyAdminRaw;

    if ($lazyAdminHost !== '' && str_contains($lazyAdminHost, '://')) {
        $lazyAdminHost = (string) (parse_url($lazyAdminHost, PHP_URL_HOST) ?? '');
    }
    $lazyAdminHost = preg_replace('/:\d+$/', '', $lazyAdminHost) ?? '';

    $lazyDocsRaw     = trim((string) env('DOCS_DOMAIN', ''));
    $lazyDocsHost    = $lazyDocsRaw;

    if ($lazyDocsHost !== '' && str_contains($lazyDocsHost, '://')) {
        $lazyDocsHost = (string) (parse_url($lazyDocsHost, PHP_URL_HOST) ?? '');
    }
    $lazyDocsHost = preg_replace('/:\d+$/', '', $lazyDocsHost) ?? '';

    $requestHost           = strtolower(trim((string) $request->getHost()));
    $normalizedPublicHost  = strtolower(trim((string) ($lazyPublicHost ?? '')));
    $normalizedAdminHost   = strtolower(trim((string) $lazyAdminHost));
    $normalizedDocsHost    = strtolower(trim((string) $lazyDocsHost));

    if ($normalizedAdminHost !== '' && $requestHost === $normalizedAdminHost) {
        abort(404);
    }

    if ($normalizedDocsHost !== '' && $requestHost === $normalizedDocsHost) {
        abort(404);
    }

    if ($normalizedPublicHost !== '' && $requestHost !== $normalizedPublicHost) {
        abort(404);
    }

    return redirect('/id', 302);
});
