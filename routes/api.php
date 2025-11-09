<?php


use App\Http\Controllers\api\MbarangController;
use App\Http\Controllers\api\MCustomerController;
use App\Http\Controllers\api\MkategoriController;
use App\Http\Controllers\api\TransaksipenerimaanController;
use App\Http\Controllers\api\TransaksipengirimanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::apiResource('mbarang', MbarangController::class);
Route::apiResource('mcustomer', MCustomerController::class);

Route::apiResource('transaksipenerimaan', TransaksipenerimaanController::class);
Route::apiResource('transaksipengiriman', TransaksipengirimanController::class);
