<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransaksiRequest;
use App\Http\Resources\TransaksiResource;
use App\Models\TransaksiModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $transaksi = TransaksiModel::with('customer', 'barang')->paginate(10);

            if ($transaksi->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data transaksi kosong',
                    'data_transaksi' => []
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data transaksi berhasil diambil',
                'data_transaksi' => TransaksiResource::collection($transaksi)->response()->getData()
            ], status: 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransaksiRequest $request)
    {
        DB::beginTransaction();

        try {
            // Validasi stok terlebih dahulu
            $validated = $request->validated();

            $transaksiterakhir = DB::table('dbo_riwayat_stok')
                ->where('id_barang', $validated['id_barang'])
                ->orderBy('id_riwayat', 'desc')
                ->lockForUpdate()
                ->first();

            $stok_tersedia = 0;

            if ($transaksiterakhir) {
                $stok_tersedia = $transaksiterakhir->stok_akhir_isi;
            } else {
                // Jika belum ada riwayat, ambil dari master barang
                $masterBarang = DB::table('dbo_master_barang')
                    ->where('id_barang', $validated['id_barang'])
                    ->first();
                $stok_tersedia = $masterBarang->stok_tabung ?? 0;
            }

            $total_kebutuhan = $validated['jumlah_isi'] + ($validated['pinjam_tabung'] ?? 0);

            if ($stok_tersedia < $total_kebutuhan) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Stok Tabung tidak mencukupi',
                    'data' => [
                        'stok_tersedia' => $stok_tersedia,
                        'kebutuhan' => $total_kebutuhan
                    ]
                ], 400);
            }

            // Hitung total harga
            $validated['total_harga'] = $validated['jumlah_isi'] * $validated['harga_satuan'];

            // Set default status jika tidak ada
            $validated['status'] = $validated['status'] ?? 'pending';

            // Create transaksi - HANYA TRIGGER yang membuat riwayat stok
            $transaksi = TransaksiModel::create($validated);

            // PENTING: Commit dulu sebelum mengambil riwayat
            DB::commit();

            // Tunggu sebentar untuk memastikan trigger selesai
            usleep(100000); // 0.1 detik

            // Ambil riwayat stok yang dibuat oleh trigger
            $riwayatbaru = DB::table('dbo_riwayat_stok')
                ->where('id_transaksi', $transaksi->id_pengiriman)
                ->orderBy('id_riwayat', 'desc')
                ->first();

            $transaksi->load('customer', 'barang');

            return response()->json([
                'status' => true,
                'message' => 'Transaksi berhasil ditambahkan',
                'data_transaksi' => new TransaksiResource($transaksi),
                'stok_info' => $riwayatbaru ? [
                    'sebelum' => [
                        'stok_isi' => $riwayatbaru->stok_awal_isi,
                        'stok_kosong' => $riwayatbaru->stok_awal_kosong
                    ],
                    'sesudah' => [
                        'stok_isi' => $riwayatbaru->stok_akhir_isi,
                        'stok_kosong' => $riwayatbaru->stok_akhir_kosong
                    ]
                ] : null,
                'debug_info' => [
                    'id_pengiriman' => $transaksi->id_pengiriman,
                    'riwayat_count' => DB::table('dbo_riwayat_stok')
                        ->where('id_transaksi', $transaksi->id_pengiriman)
                        ->count()
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error creating transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $transaksi = TransaksiModel::with('customer', 'barang')->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Detail transaksi berhasil diambil',
                'data_transaksi' => new TransaksiResource($transaksi)
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching transaction detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransaksiRequest $request, string $id)
    {
        DB::beginTransaction();

        try {
            $transaksi = TransaksiModel::findOrFail($id);
            $validated = $request->validated();

            // Cek apakah ada perubahan yang mempengaruhi stok
            $stokChanged = false;
            if (isset($validated['jumlah_isi']) && $validated['jumlah_isi'] != $transaksi->jumlah_isi) {
                $stokChanged = true;
            }
            if (isset($validated['pinjam_tabung']) && $validated['pinjam_tabung'] != $transaksi->pinjam_tabung) {
                $stokChanged = true;
            }
            if (isset($validated['jumlah_kosong']) && $validated['jumlah_kosong'] != $transaksi->jumlah_kosong) {
                $stokChanged = true;
            }

            // HANYA validasi stok jika ada perubahan, JANGAN hapus riwayat dulu
            if ($stokChanged) {
                // Ambil riwayat transaksi ini untuk kalkulasi balik
                $riwayatLama = DB::table('dbo_riwayat_stok')
                    ->where('id_transaksi', $transaksi->id_pengiriman)
                    ->first();

                // Ambil stok SEBELUM transaksi ini (untuk recalculate)
                $stokSebelumTransaksi = $riwayatLama ? $riwayatLama->stok_awal_isi : 0;

                // Hitung kebutuhan baru
                $newJumlahIsi = $validated['jumlah_isi'] ?? $transaksi->jumlah_isi;
                $newPinjamTabung = $validated['pinjam_tabung'] ?? $transaksi->pinjam_tabung;
                $total_kebutuhan = $newJumlahIsi + $newPinjamTabung;

                if ($stokSebelumTransaksi < $total_kebutuhan) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Stok Tabung tidak mencukupi',
                        'data' => [
                            'stok_tersedia' => $stokSebelumTransaksi,
                            'kebutuhan' => $total_kebutuhan
                        ]
                    ], 400);
                }
            }

            // Hitung total harga jika ada perubahan
            if (isset($validated['jumlah_isi']) || isset($validated['harga_satuan'])) {
                $jumlah_isi = $validated['jumlah_isi'] ?? $transaksi->jumlah_isi;
                $harga_satuan = $validated['harga_satuan'] ?? $transaksi->harga_satuan;
                $validated['total_harga'] = $jumlah_isi * $harga_satuan;
            }

            // Update transaksi - TRIGGER UPDATE akan handle riwayat stok
            $transaksi->update($validated);

            DB::commit();

            // Tunggu sebentar untuk memastikan trigger selesai
            usleep(100000);

            // Ambil riwayat stok terbaru
            $riwayatbaru = DB::table('dbo_riwayat_stok')
                ->where('id_transaksi', $transaksi->id_pengiriman)
                ->orderBy('id_riwayat', 'desc')
                ->first();

            $transaksi->load('customer', 'barang');

            return response()->json([
                'status' => true,
                'message' => 'Transaksi berhasil diupdate',
                'data_transaksi' => new TransaksiResource($transaksi),
                'stok_info' => $riwayatbaru ? [
                    'sebelum' => [
                        'stok_isi' => $riwayatbaru->stok_awal_isi,
                        'stok_kosong' => $riwayatbaru->stok_awal_kosong
                    ],
                    'sesudah' => [
                        'stok_isi' => $riwayatbaru->stok_akhir_isi,
                        'stok_kosong' => $riwayatbaru->stok_akhir_kosong
                    ]
                ] : null,
                'debug_info' => [
                    'id_pengiriman' => $transaksi->id_pengiriman,
                    'riwayat_count' => DB::table('dbo_riwayat_stok')
                        ->where('id_transaksi', $transaksi->id_pengiriman)
                        ->count(),
                    'stok_changed' => $stokChanged
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error updating transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $transaksi = TransaksiModel::findOrFail($id);



            // Load relasi untuk response
            $transaksi->load('customer', 'barang');
            $transaksiData = new TransaksiResource($transaksi);

            // Ambil info riwayat stok sebelum dihapus
            $riwayatStok = DB::table('dbo_riwayat_stok')
                ->where('id_transaksi', $transaksi->id_pengiriman)
                ->first();

            // Hapus riwayat stok terkait transaksi ini
            $deletedRiwayat = DB::table('dbo_riwayat_stok')
                ->where('id_transaksi', $transaksi->id_pengiriman)
                ->delete();

            // Hapus transaksi
            $transaksi->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Transaksi berhasil dihapus',
                'deleted_transaction' => $transaksiData,
                'stock_restored' => $riwayatStok ? [
                    'barang' => $transaksi->barang->nama_barang,
                    'stok_dikembalikan' => $transaksi->jumlah_isi + ($transaksi->pinjam_tabung ?? 0) . ' tabung isi',
                    'riwayat_dihapus' => $deletedRiwayat > 0
                ] : null
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error deleting transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
