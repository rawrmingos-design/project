<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\GatewayController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\SandboxOrderApiController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PricelistController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\RecentPurchasesController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\PostmanExportController;
use App\Http\Controllers\Api\SubscriptionWebhookController;
use App\Http\Controllers\Api\TenantRegistrationController;

use App\Http\Controllers\Api\BotWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/subdomain/check', [TenantRegistrationController::class, 'checkSubdomain'])
    ->middleware('throttle:30,1')
    ->name('api.tenant.subdomain.check');
Route::post('/tenant/register', [TenantRegistrationController::class, 'register'])
    ->middleware('throttle:10,1')
    ->name('api.tenant.register');
Route::post('/webhooks/subscription', SubscriptionWebhookController::class)
    ->middleware('throttle:subscription-callback')
    ->name('api.webhooks.subscription');

Route::prefix('v1')->middleware(['add.api.version'])->group(function () {
    Route::post('/balance', [OrderApiController::class,'balance'])
        ->middleware(['throttle:reseller-api-balance', 'auth.api', 'reseller.ip.enforce']);
    Route::post('/category', [OrderApiController::class,'category'])
        ->middleware(['throttle:reseller-api-category', 'auth.api', 'reseller.ip.enforce']);
    Route::post('/variant', [OrderApiController::class,'listVariant'])
        ->middleware(['throttle:reseller-api-variant', 'auth.api', 'reseller.ip.enforce']);
    Route::post('/order', [OrderApiController::class,'order'])
        ->middleware(['throttle:reseller-api-order', 'auth.api', 'reseller.ip.enforce']);
    Route::post('/status-order/{invoice}', [OrderApiController::class,'statusOrder'])
        ->middleware(['throttle:reseller-api-status', 'auth.api', 'reseller.ip.enforce']);

    Route::prefix('sandbox')->group(function () {
        Route::post('/balance', [SandboxOrderApiController::class, 'balance'])
            ->middleware(['throttle:reseller-api-balance', 'auth.sandbox.api']);
        Route::post('/category', [SandboxOrderApiController::class, 'category'])
            ->middleware(['throttle:reseller-api-category', 'auth.sandbox.api']);
        Route::post('/variant', [SandboxOrderApiController::class, 'listVariant'])
            ->middleware(['throttle:reseller-api-variant', 'auth.sandbox.api']);
        Route::post('/order', [SandboxOrderApiController::class, 'order'])
            ->middleware(['throttle:reseller-api-order', 'auth.sandbox.api']);
        Route::post('/status-order/{invoice}', [SandboxOrderApiController::class, 'statusOrder'])
            ->middleware(['throttle:reseller-api-status', 'auth.sandbox.api']);
        Route::post('/simulate-status/{invoice}', [SandboxOrderApiController::class, 'simulateStatus'])
            ->middleware(['throttle:reseller-api-order', 'auth.sandbox.api']);
    });
});


/*
|--------------------------------------------------------------------------
| Provider Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle webhooks from external providers for order status
| updates and other notifications.
|
*/

Route::prefix('webhooks')->group(function () {
    Route::post('/digiflazz', [WebhookController::class, 'digiflazz'])
        ->middleware(['throttle:provider-webhook', 'inbound.whitelist:supplier_callback,digiflazz,log_only'])
        ->name('webhooks.digiflazz');
    Route::post('/bangjeff', [WebhookController::class, 'bangjeff'])
        ->middleware(['throttle:provider-webhook', 'inbound.whitelist:supplier_callback,bangjeff,log_only'])
        ->name('webhooks.bangjeff');
    Route::post('/topupedia', [WebhookController::class, 'generic'])
        ->defaults('provider', 'topupedia')
        ->middleware(['throttle:provider-webhook', 'inbound.whitelist:supplier_callback,topupedia,log_only'])
        ->name('webhooks.topupedia');
    Route::post('/apigames', [WebhookController::class, 'generic'])
        ->defaults('provider', 'apigames')
        ->middleware(['throttle:provider-webhook', 'inbound.whitelist:supplier_callback,apigames,log_only'])
        ->name('webhooks.apigames');
    Route::post('/{provider}', [WebhookController::class, 'generic'])
        ->middleware(['throttle:provider-webhook', 'inbound.whitelist:supplier_callback,@provider,log_only'])
        ->name('webhooks.generic');
});

// ── BOT WEBHOOKS (TELEGRAM / WHATSAPP) ──────────────────
Route::prefix('webhooks/bot')->middleware('throttle:bot-webhook')->group(function () {
    Route::post('/telegram', [BotWebhookController::class, 'telegram'])
        ->middleware('inbound.whitelist:bot_webhook,telegram,enforce')
        ->name('webhooks.bot.telegram');
    Route::post('/fonnte', [BotWebhookController::class, 'fonnte'])
        ->middleware('inbound.whitelist:bot_webhook,fonnte,enforce')
        ->name('webhooks.bot.fonnte');
    Route::post('/openwa', [BotWebhookController::class, 'openwa'])
        ->middleware('inbound.whitelist:bot_webhook,openwa,enforce')
        ->name('webhooks.bot.openwa');
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:public-login');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:public-register');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:password-recovery-request');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:password-reset-submit');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});


// Route::middleware(['auth:sanctum', 'prevent.xss', 'csrf'])
//     ->group(function () {
//         Route::get('/user', function (Request $request) {
//             return $request->user();
//         });
//     });

// ── PUBLIC STOREFRONT ────────────────────────────────────
Route::get('/home', [HomeController::class, 'index'])->middleware('throttle:public-api-read');
Route::get('/categories/search', [CategoryController::class, 'search'])->middleware('throttle:public-search');
Route::get('/categories/{kode}', [CategoryController::class, 'show'])->middleware('throttle:public-api-expensive-read');
Route::get('/price-list', [PricelistController::class, 'index'])->middleware('throttle:public-api-expensive-read');
Route::get('/reviews', [ReviewController::class, 'index'])->middleware('throttle:public-api-expensive-read');
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->middleware('throttle:public-api-expensive-read');
Route::get('/articles', [ArticleController::class, 'index'])->middleware('throttle:public-api-read');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])->middleware('throttle:public-api-read');
Route::get('/recent-purchases', [RecentPurchasesController::class, 'index'])
    ->middleware('throttle:public-api-read')
    ->name('recent-purchases.index');
Route::get('/content/{slug}', [ContentController::class, 'show'])->middleware('throttle:public-api-read');
// ── ORDER FLOW (HEADLESS) ──────────────────────────────
Route::prefix('v2')->middleware('tenant.resolve')->group(function () {
    Route::post('/order/price', [OrderController::class, 'price'])->middleware('throttle:public-order-price');
    Route::post('/order/confirm', [OrderController::class, 'confirm'])->middleware('throttle:public-order-confirm');
    Route::post('/order/store', [OrderController::class, 'store'])->middleware('throttle:public-order-submit');
    Route::get('/order/status/{order_id}', [OrderController::class, 'show'])->middleware('throttle:public-status');
    Route::post('/order/voucher', [OrderController::class, 'validateVoucher'])->middleware('throttle:public-voucher');
});

// ── GATEWAY MVP ─────────────────────────────────────────
Route::prefix('gateway')->middleware('tenant.resolve')->group(function () {
    Route::get('/category-types', [GatewayController::class, 'categoryTypes'])->middleware('throttle:public-api-read');
    Route::get('/categories', [GatewayController::class, 'categories'])->middleware('throttle:public-api-read');
    Route::get('/products', [GatewayController::class, 'products'])->middleware('throttle:public-api-read');
    Route::get('/services', [GatewayController::class, 'services'])->middleware('throttle:public-api-read');
    Route::get('/services/{service_id}', [GatewayController::class, 'serviceDetail'])->middleware('throttle:public-api-expensive-read');
    Route::get('/payment-methods', [GatewayController::class, 'paymentMethods'])->middleware('throttle:public-api-read');
    Route::post('/vouchers/validate', [GatewayController::class, 'validateVoucher'])->middleware('throttle:public-voucher');
    Route::post('/price', [GatewayController::class, 'price'])->middleware('throttle:public-order-price');
    Route::post('/check-id', [GatewayController::class, 'checkId'])->middleware('throttle:public-account-check');
    Route::post('/invoices', [GatewayController::class, 'createInvoice'])->middleware('throttle:public-invoice-create');
    Route::get('/invoices/{order_id}', [GatewayController::class, 'status'])->middleware('throttle:public-status');
});

// ── POSTMAN EXPORT ──────────────────────────────────────
Route::prefix('postman')->group(function () {
    Route::get('/collection', [PostmanExportController::class, 'collection'])
        ->name('api.postman.collection');
    Route::get('/environment', [PostmanExportController::class, 'environment'])
        ->middleware('auth:sanctum')
        ->name('api.postman.environment');
});

