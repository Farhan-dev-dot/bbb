<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\RiwayatStokModel;
use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Request;

class RiwayatTransaksiController extends Controller
{
    public function index(Request $request)
    {
        if (!auth('api')->check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        try {
            $query = RiwayatStokModel::with(['barang', 'barangkeluar', 'barangmasuk']);

            // Filter by tanggal_masuk from (tanggal mulai)
            if ($request->filled('tanggal_dari')) {
                $query->whereDate('tanggal_transaksi', '>=', $request->tanggal_dari);
            }

            // Filter by tanggal_masuk to (tanggal akhir)
            if ($request->filled('tanggal_sampai')) {
                $query->whereDate('tanggal_transaksi', '<=', $request->tanggal_sampai);
            }

            if ($request->has('tipe_transaksi')) {
                $query->where('tipe_transaksi', $request->input('tipe_transaksi'));
            }

            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('barang', function ($subQuery) use ($keyword) {
                        $subQuery->where('id_barang', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('nama_barang', 'LIKE', '%' . $keyword . '%');
                    });
                });
            }

            // Pagination
            $perPage = $request->input('per_page', 10);
            $currentPage = $request->input('page', 1);

            $riwayats = $query->paginate($perPage, ['*'], 'page', $currentPage);

            if ($riwayats->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data riwayat stok kosong',
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
                'message' => 'Data riwayat stok berhasil diambil',
                'data' => $riwayats->items(),
                'current_page' => $riwayats->currentPage(),
                'per_page' => $riwayats->perPage(),
                'total' => $riwayats->total(),
                'total_page' => $riwayats->lastPage(),
                'has_next_page' => $riwayats->hasMorePages(),
                'has_prev_page' => $riwayats->currentPage() > 1,
                'from' => $riwayats->firstItem(),
                'to' => $riwayats->lastItem(),
                'search_params' => [
                    'tipe_transaksi' => $request->input('tipe_transaksi'),
                    'id_barang' => $request->input('id_barang'),
                    'tanggal_dari' => $request->input('tanggal_dari'),
                    'tanggal_sampai' => $request->input('tanggal_sampai'),
                    'per_page' => $perPage,
                    'page' => $currentPage
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data riwayat stok',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
