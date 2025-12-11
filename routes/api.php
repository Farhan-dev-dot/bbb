<?php


use App\Http\Controllers\api\auth\AuthController;
use App\Http\Controllers\api\DashboardController;
use App\Http\Controllers\api\MbarangController;
use App\Http\Controllers\api\MCustomerController;
use App\Http\Controllers\api\MkategoriController;
use App\Http\Controllers\api\RiwayatTransaksiController;
use App\Http\Controllers\api\TransaksiController;
use App\Http\Controllers\api\TransaksipenerimaanController;
use App\Http\Controllers\api\TransaksipengirimanController;
use App\Http\Controllers\api\BarangMasukController;
use App\Http\Controllers\api\BarangKeluarController;
use App\Http\Controllers\api\StokOpnameController;
use App\Http\Controllers\api\LaporanController;
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
    // Dashboard endpoints
    Route::get('/total-customer', [DashboardController::class, 'totalCustomer']);
    Route::get('/total-barang', [DashboardController::class, 'totalBarang']);
    Route::get('/total-stok-akhir-isi', [DashboardController::class, 'totalStokAkhirIsi']);
    Route::get('/total-stok-akhir-kosong', [DashboardController::class, 'totalStokAkhirKosong']);

    // Charts and analytics
    Route::get('/total-pendapatan-harian', [DashboardController::class, 'totalPendapatanHariIni']);
    Route::get('/total-transaksi-harian', [DashboardController::class, 'totalTransaksiHariIni']);
    Route::get('/pendapatan-per-tanggal', [DashboardController::class, 'totalPendapatanPerTanggal']);
    Route::get('/pendapatan-per-tahun', [DashboardController::class, 'totalPendapatanPerTahun']);

    // Dashboard summary
    Route::get('/summary', [DashboardController::class, 'dashboardSummary']);

    // Master data endpoints
    Route::apiResource('master-barang', MbarangController::class);

    Route::apiResource('master-customer', MCustomerController::class);

    // ===== SISTEM MANAJEMEN STOK DEPOT GAS =====

    // 1. BARANG MASUK - Menambah stok dari supplier atau retur customer
    Route::apiResource('barang-masuk', BarangMasukController::class);
    Route::get('/riwayat-pembelian', [RiwayatTransaksiController::class, 'index']);


    // 2. BARANG KELUAR - Mengurangi stok untuk pengiriman ke customer
    Route::apiResource('barang-keluar', BarangKeluarController::class);

    // 3. STOK OPNAME - Koreksi stok berdasarkan fisik
    Route::prefix('stok-opname')->group(function () {
        Route::get('/', [StokOpnameController::class, 'index']);                    // List semua opname dengan filter
        Route::get('/current-stok', [StokOpnameController::class, 'getCurrentStok']); // Stok REAL-TIME untuk koreksi
        Route::get('/detail-barang/{id_barang}', [StokOpnameController::class, 'getDetailBarang']); // Detail barang untuk form
        Route::get('/history/{id_barang}', [StokOpnameController::class, 'historyOpname']); // History opname per barang
        Route::post('/koreksi', [StokOpnameController::class, 'koreksiStok']);      // Lakukan koreksi stok
        Route::get('/laporanstok', [StokOpnameController::class, 'laporanStok']);   // Laporan semua record opname
        Route::get('/stok-minimum', [StokOpnameController::class, 'stokMinimum']);  // Barang stok minimum
        Route::delete('/hapus/{id_riwayat}', [StokOpnameController::class, 'destroy']); // Hapus & rollback opname
    });

    // 4. LAPORAN STOK DAN TRANSAKSI
    Route::prefix('laporan')->group(function () {
        Route::get('/mutasi-stok-harian', [LaporanController::class, 'mutasiStokHarian']);
        Route::get('/mutasi-stok-bulanan', [LaporanController::class, 'mutasiStokBulanan']);
        Route::get('/barang-masuk', [LaporanController::class, 'laporanBarangMasuk']);
        Route::get('/barang-keluar', [LaporanController::class, 'laporanBarangKeluar']);
        Route::get('/cashflow', [LaporanController::class, 'laporanCashflow']);
        Route::get('/dashboard-summary', [LaporanController::class, 'dashboardSummary']);
        Route::get('/laporan-transaksi', [LaporanController::class, 'LaporanTransaksi']);
    });
});
