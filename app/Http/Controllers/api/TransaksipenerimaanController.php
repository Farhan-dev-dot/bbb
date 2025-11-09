<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransaksipenerimaanRequest;
use App\Http\Resources\TransaksipenerimaanResource;
use App\Models\TransaksipenerimaanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksipenerimaanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $penerimaan = TransaksipenerimaanModel::with('barang')->paginate(10);

            if ($penerimaan->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data barang penerimaan kosong',
                    'transaksi_penerimaan' => []
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang penerimaan berhasil diambil',
                'transaksi_penerimaan' => TransaksipenerimaanResource::collection($penerimaan)->response()->getData()
            ], status: 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data barang penerimaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransaksipenerimaanRequest $request)
    {
        try {
            DB::beginTransaction();


            $penerimaan = TransaksipenerimaanModel::create($request->validated());
            $penerimaan->load("barang");


            DB::table('dbo_stok_harian')->insert([
                "id_penerimaan" => $penerimaan->id_penerimaan,
                "id_barang" => $penerimaan->id_barang,
                "tanggal" => $penerimaan->tanggal_penerimaan,
                "stok_isi" => $penerimaan->tabung_isi ?? 0,
                "stok_kosong" => $penerimaan->tabung_kosong ?? 0,
                "pinjam_tabung" => $penerimaan->pinjam_tabung ?? 0,
                "created_at" => now(),
                "updated_at" => now(),
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data transaksi penerimaan berhasil ditambahkan',
                'transaksi_penerimaan' => new TransaksipenerimaanResource($penerimaan)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan data transaksi penerimaan',
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

    public function update(TransaksipenerimaanRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            // Manual find berdasarkan primary key
            $penerimaan = TransaksipenerimaanModel::findOrFail($id);

            $penerimaan->update($request->validated());
            $penerimaan->refresh();
            $penerimaan->load("barang");

            // Update stok harian
            DB::table('dbo_stok_harian')
                ->where('id_penerimaan', $penerimaan->id_penerimaan)
                ->update([
                    "id_barang" => $penerimaan->id_barang,
                    "tanggal" => date('Y-m-d', strtotime($penerimaan->tanggal_penerimaan)),
                    "stok_isi" => $penerimaan->tabung_isi ?? 0,
                    "stok_kosong" => $penerimaan->tabung_kosong ?? 0,
                    "pinjam_tabung" => $penerimaan->pinjam_tabung ?? 0,
                ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data transaksi penerimaan berhasil diperbarui',
                'transaksi_penerimaan' => new TransaksipenerimaanResource($penerimaan)
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data transaksi penerimaan',
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
            $penerimaan = TransaksipenerimaanModel::findOrFail($id);

            $penerimaan->delete();

            // Delete stok harian
            DB::table('dbo_stok_harian')
                ->where('id_penerimaan', $penerimaan->id_penerimaan)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data transaksi penerimaan berhasil dihapus',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data transaksi penerimaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
