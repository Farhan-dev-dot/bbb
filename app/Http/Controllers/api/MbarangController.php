<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterbarangRequest;
use App\Http\Resources\MasterBarangResource;
use App\Models\MasterBarangModel;
use Illuminate\Http\Request;

class MbarangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $barangs = MasterBarangModel::paginate(10);

            if ($barangs->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data barang kosong',
                    'master_barang' => []
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil diambil',
                'master_barang' => MasterBarangResource::collection($barangs)->response()->getData()
            ], status: 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MasterbarangRequest $request)
    {
        try {
            $barangs = MasterBarangModel::create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil dibuat',
                'data' => new MasterBarangResource($barangs)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat data barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MasterBarangModel $mbarang)
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil ditemukan',
                'data' => new MasterBarangResource($mbarang)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data barang tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MasterbarangRequest $request, MasterBarangModel $mbarang)
    {
        try {
            $mbarang->update($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil diupdate',
                'data' => new MasterBarangResource($mbarang)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate data barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterBarangModel $mbarang)
    {
        try {
            $mbarang->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
