<?php


use App\Http\Controllers\api\auth\AuthController;
use App\Http\Controllers\api\MbarangController;
use App\Http\Controllers\api\MCustomerController;
use App\Http\Controllers\api\MkategoriController;
use App\Http\Controllers\api\TransaksiController;
use App\Http\Controllers\api\TransaksipenerimaanController;
use App\Http\Controllers\api\TransaksipengirimanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('mbarang', MbarangController::class);
    Route::apiResource('mcustomer', MCustomerController::class);
    Route::apiResource('transaksi', TransaksiController::class);

    // Routes tambahan transaksi
    Route::patch('transaksi/{id}/status', [TransaksiController::class, 'updateStatus']);
    Route::get('laporan-stok', [TransaksiController::class, 'laporanStok']);
});
