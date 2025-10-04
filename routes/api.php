<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderApiController;

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


// Route::middleware(['auth:sanctum', 'prevent.xss', 'csrf'])
//     ->group(function () {
//         Route::get('/user', function (Request $request) {
//             return $request->user();
//         });
//     });
