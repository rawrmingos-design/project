<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
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
use App\Http\Controllers\Api\RecentPurchasesController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\PostmanExportController;

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

Route::prefix('v1')->middleware(['add.api.version'])->group(function () {
    Route::post('/balance', [OrderApiController::class,'balance'])
        ->middleware(['throttle:reseller-api-balance', 'auth.api']);
    Route::post('/category', [OrderApiController::class,'category'])
        ->middleware(['throttle:reseller-api-category', 'auth.api']);
    Route::post('/variant', [OrderApiController::class,'listVariant'])
        ->middleware(['throttle:reseller-api-variant', 'auth.api']);
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
        ->middleware('inbound.whitelist:supplier_callback,digiflazz,log_only')
        ->name('webhooks.digiflazz');
    Route::post('/bangjeff', [WebhookController::class, 'bangjeff'])
        ->middleware('inbound.whitelist:supplier_callback,bangjeff,log_only')
        ->name('webhooks.bangjeff');
    Route::post('/topupedia', [WebhookController::class, 'generic'])
        ->defaults('provider', 'topupedia')
        ->middleware('inbound.whitelist:supplier_callback,topupedia,log_only')
        ->name('webhooks.topupedia');
    Route::post('/apigames', [WebhookController::class, 'generic'])
        ->defaults('provider', 'apigames')
        ->middleware('inbound.whitelist:supplier_callback,apigames,log_only')
        ->name('webhooks.apigames');
    Route::post('/{provider}', [WebhookController::class, 'generic'])
        ->middleware('inbound.whitelist:supplier_callback,@provider,log_only')
        ->name('webhooks.generic');
});
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});


// Route::middleware(['auth:sanctum', 'prevent.xss', 'csrf'])
//     ->group(function () {
//         Route::get('/user', function (Request $request) {
//             return $request->user();
//         });
//     });

// ── PUBLIC STOREFRONT ────────────────────────────────────
Route::get('/home', [HomeController::class, 'index']);
Route::get('/categories/search', [CategoryController::class, 'search']);
Route::get('/categories/{kode}', [CategoryController::class, 'show']);
Route::get('/price-list', [PricelistController::class, 'index']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/leaderboard', [LeaderboardController::class, 'index']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{slug}', [ArticleController::class, 'show']);
Route::get('/recent-purchases', [RecentPurchasesController::class, 'index']);
Route::get('/content/{slug}', [ContentController::class, 'show']);
// ── ORDER FLOW (HEADLESS) ──────────────────────────────
Route::prefix('v2')->group(function () {
    Route::post('/order/price', [OrderController::class, 'price']);
    Route::post('/order/confirm', [OrderController::class, 'confirm']);
    Route::post('/order/store', [OrderController::class, 'store']);
    Route::get('/order/status/{order_id}', [OrderController::class, 'show']);
    Route::post('/order/voucher', [OrderController::class, 'validateVoucher']);
});

// ── POSTMAN EXPORT ──────────────────────────────────────
Route::prefix('postman')->group(function () {
    Route::get('/collection', [PostmanExportController::class, 'collection'])
        ->name('api.postman.collection');
    Route::get('/environment', [PostmanExportController::class, 'environment'])
        ->middleware('auth:sanctum')
        ->name('api.postman.environment');
});

