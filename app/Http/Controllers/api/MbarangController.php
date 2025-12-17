<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterbarangRequest;
use App\Http\Resources\MasterbarangResource;
use App\Models\MasterBarangModel;
use Illuminate\Http\Request;

class MbarangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!auth('api')->check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        try {
            $query = MasterBarangModel::query();

            // Search by nama barang
            if ($request->filled('namakeyword')) {
                $query->where('nama_barang', 'LIKE', '%' . $request->namakeyword . '%');
            }

            // Global search (search in kode barang and nama barang only)
            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where('nama_barang', 'LIKE', '%' . $keyword . '%');
            }

            // Sort options
            $sortBy = $request->input('sortby', 'created_at');
            $sortOrder = $request->input('sortorder', 'desc');

            // Validate sort column to prevent SQL injection
            $allowedSortColumns = [
                'id_barang',
                'nama_barang',
                'kapasitas',
                'harga_jual',
                'stok_tabung_isi',
                'stok_tabung_kosong',
                'stok_minimum',
                'created_at',
                'updated_at'
            ];

            if (in_array($sortBy, $allowedSortColumns)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = $request->input('per_page', 10);
            $currentPage = $request->input('page', 1);

            $barangs = $query->paginate($perPage, ['*'], 'page', $currentPage);

            if ($barangs->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data barang kosong',
                    'data' => [],
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_page' => 0,
                    'has_next_page' => false,
                    'has_prev_page' => false,
                    'from' => null,
                    'to' => null
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil diambil',
                'data' => MasterbarangResource::collection($barangs->items()),
                'current_page' => $barangs->currentPage(),
                'per_page' => $barangs->perPage(),
                'total' => $barangs->total(),
                'total_page' => $barangs->lastPage(),
                'has_next_page' => $barangs->hasMorePages(),
                'has_prev_page' => $barangs->currentPage() > 1,
                'from' => $barangs->firstItem(),
                'to' => $barangs->lastItem(),
                'search_params' => [
                    'kodekeyword' => $request->input('kodekeyword'),
                    'namakeyword' => $request->input('namakeyword'),
                    'keyword' => $request->input('keyword'),
                    'sortby' => $request->input('sortby'),
                    'sortorder' => $request->input('sortorder')
                ]
            ], 200);
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
        if (!auth('api')->check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        try {
            $barangs = MasterBarangModel::create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil dibuat',
                'data' => new MasterbarangResource($barangs)
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
    public function show(string $id)
    {
        try {
            $mbarang = MasterBarangModel::find($id);

            if (!$mbarang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil diambil',
                'data' => new MasterbarangResource($mbarang)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(MasterbarangRequest $request, string $id)
    {
        try {
            $validatedData = $request->validated();

            // Cari barang berdasarkan ID
            $mbarang = MasterBarangModel::find($id);

            if (!$mbarang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang tidak ditemukan'
                ], 404);
            }

            // Update data
            $updateResult = $mbarang->update($validatedData);

            if (!$updateResult) {
                throw new \Exception('Gagal melakukan update ke database');
            }

            // Re-fetch data dari database untuk memastikan data terbaru
            $updatedBarang = MasterBarangModel::find($id);

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil diupdate',
                'data' => [
                    'id_barang' => $updatedBarang->id_barang,
                    'nama_barang' => $updatedBarang->nama_barang,
                    'kapasitas' => $updatedBarang->kapasitas,
                    'harga_jual' => $updatedBarang->harga_jual,
                    'stok_tabung_isi' => $updatedBarang->stok_tabung_isi,
                    'stok_tabung_kosong' => $updatedBarang->stok_tabung_kosong,
                    'created_at' => $updatedBarang->created_at,
                    'updated_at' => $updatedBarang->updated_at,
                ]
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
    public function destroy($id)
    {
        try {
            $mbarang = MasterBarangModel::find($id);

            if (!$mbarang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang tidak ditemukan'
                ], 404);
            }

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
