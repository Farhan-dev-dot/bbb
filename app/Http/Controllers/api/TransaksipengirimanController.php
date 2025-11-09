<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransaksipengirimanRequest;
use App\Http\Resources\TransaksipengirimanResource;
use App\Models\TransaksipengirimanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksipengirimanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $pengiriman = TransaksipengirimanModel::with('barang', 'customer')->paginate(10);

            if ($pengiriman->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data barang pengiriman kosong',
                    'transaksi_pengiriman' => []
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang pengiriman berhasil diambil',
                'transaksi_pengiriman' => TransaksipengirimanResource::collection($pengiriman)->response()->getData()
            ], status: 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data barang pengiriman',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransaksipengirimanRequest $request)
    {
        try {
            DB::beginTransaction();


            $pengiriman = TransaksipengirimanModel::create($request->validated());

            $pengiriman->load("barang");

            $pengiriman->load("customer");

            DB::table('dbo_stok_harian')->insert([
                "id_pengeluaran" => $pengiriman->id_pengiriman,
                "id_barang" => $pengiriman->id_barang,
                "tanggal" => $pengiriman->tanggal_pengiriman,
                "stok_isi" => $pengiriman->tabung_isi ?? 0,
                "stok_kosong" => $pengiriman->tabung_kosong ?? 0,
                "pinjam_tabung" => $pengiriman->pinjam_tabung ?? 0,
                "created_at" => now(),
                "updated_at" => now(),
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data transaksi pengiriman berhasil ditambahkan',
                'transaksi_pengiriman' => new TransaksipengirimanResource($pengiriman)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan data transaksi pengiriman',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransaksipengirimanRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            // Manual find berdasarkan primary key
            $pengiriman = TransaksipengirimanModel::findOrFail($id);

            $pengiriman->update($request->validated());
            $pengiriman->refresh();
            $pengiriman->load("barang");
            $pengiriman->load("customer");

            // Update stok harian
            DB::table('dbo_stok_harian')
                ->where('id_pengeluaran', $pengiriman->id_pengiriman)
                ->update([
                    "id_barang" => $pengiriman->id_barang,
                    "tanggal" => date('Y-m-d', strtotime($pengiriman->tanggal_pengiriman)),
                    "stok_isi" => $pengiriman->tabung_isi ?? 0,
                    "stok_kosong" => $pengiriman->tabung_kosong ?? 0,
                    "pinjam_tabung" => $pengiriman->pinjam_tabung ?? 0,
                ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data transaksi pengiriman berhasil diperbarui',
                'transaksi_pengiriman' => new TransaksipengirimanResource($pengiriman)
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data transaksi pengiriman',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            // Manual find berdasarkan primary key
            $pengiriman = TransaksipengirimanModel::findOrFail($id);

            $pengiriman->delete();

            // Delete stok harian
            DB::table('dbo_stok_harian')
                ->where('id_pengeluaran', $pengiriman->id_pengiriman)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data transaksi pengiriman berhasil dihapus',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data transaksi pengiriman',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
