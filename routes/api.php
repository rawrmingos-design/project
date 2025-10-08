<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\WebhookController;

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


// Route::middleware(['auth:sanctum', 'prevent.xss', 'csrf'])
//     ->group(function () {
//         Route::get('/user', function (Request $request) {
//             return $request->user();
//         });
//     });
