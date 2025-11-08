<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterkategoriRequest;
use App\Http\Resources\MasterkategoriResource;
use App\Models\MasterKategoriModel;
use Illuminate\Http\Request;

class MkategoriController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $kategoris = MasterKategoriModel::paginate(10);

            if ($kategoris->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data kategori kosong',
                    'master_kategori' => []
                ], 200);
            }
            return response()->json([
                'status' => true,
                'message' => 'Data kategori berhasil diambil',
                'master_kategori' => MasterkategoriResource::collection($kategoris)->response()->getData()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data kategori',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MasterkategoriRequest $request)
    {
        try {
            $kategori = MasterKategoriModel::create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Data kategori berhasil dibuat',
                'data' => new MasterkategoriResource($kategori)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat data kategori',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MasterKategoriModel $mkategori)
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Data kategori berhasil ditemukan',
                'data' => new MasterkategoriResource($mkategori)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data kategori tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MasterkategoriRequest $request, MasterKategoriModel $mkategori)
    {
        try {
            $mkategori->update($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Data kategori berhasil diupdate',
                'data' => new MasterkategoriResource($mkategori)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate data kategori',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterKategoriModel $mkategori)
    {
        try {
            $mkategori->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data kategori berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data kategori',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
