<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderApiController;
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

Route::middleware('auth.api')->group(function () {
    Route::post('/v1/balance', [OrderApiController::class,'balance']);
    Route::post('/v1/product', [OrderApiController::class,'product']);
    Route::post('/v1/variant', [OrderApiController::class,'listVariant']);
    Route::post('/v1/order', [OrderApiController::class,'order']);
    Route::post('/v1/status-order/{invoice}', [OrderApiController::class,'statusOrder']);
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
    Route::post('/digiflazz', [WebhookController::class, 'digiflazz'])->name('webhooks.digiflazz');
    Route::post('/bangjeff', [WebhookController::class, 'bangjeff'])->name('webhooks.bangjeff');
    Route::post('/topupedia', [WebhookController::class, 'generic'])->defaults('provider', 'topupedia')->name('webhooks.topupedia');
    Route::post('/apigames', [WebhookController::class, 'generic'])->defaults('provider', 'apigames')->name('webhooks.apigames');
    Route::post('/{provider}', [WebhookController::class, 'generic'])->name('webhooks.generic');
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
