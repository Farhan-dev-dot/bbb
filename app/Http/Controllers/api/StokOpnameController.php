<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MasterBarangModel;
use App\Models\RiwayatStokModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StokOpnameController extends Controller
{
    /**
     * Tampilkan data stok opname
     */
    public function index(Request $request)
    {
        try {
            $query = RiwayatStokModel::with('barang');

            // Filter tipe_transaksi jika ada di request
            if ($request->filled('tipe_transaksi')) {
                $query->where('tipe_transaksi', $request->tipe_transaksi);
            }

            // Sorting
            $sortBy = $request->input('sortby', 'tanggal_transaksi');
            $sortOrder = $request->input('sortorder', 'desc');
            $allowedSortColumns = [
                'id_riwayat',
                'id_barang',
                'tipe_transaksi',
                'tanggal_transaksi',
                'perubahan_isi',
                'perubahan_kosong',
                'stok_isi_setelah',
                'stok_kosong_setelah'
            ];
            if (in_array($sortBy, $allowedSortColumns)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('tanggal_transaksi', 'desc');
            }

            $stokOpname = $query->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'Data stok opname berhasil diambil',
                'data' => $stokOpname
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching stok opname',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan current stok untuk opname
     */
    public function getCurrentStok()
    {
        try {
            $masterBarang = MasterBarangModel::select([
                'id_barang',
                'kode_barang',
                'nama_barang',
                'kapasitas',
                'stok_tabung_isi',
                'stok_tabung_kosong',
                'stok_minimum'
            ])->get();

            return response()->json([
                'status' => true,
                'message' => 'Data stok saat ini berhasil diambil',
                'data' => $masterBarang
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching current stok',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lakukan koreksi stok (Stok Opname)
     */
    public function koreksiStok(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'corrections' => 'required|array|min:1',
            'corrections.*.id_barang' => 'required|exists:dbo_master_barang,id_barang',
            'corrections.*.stok_isi_fisik' => 'required|integer|min:0',
            'corrections.*.stok_kosong_fisik' => 'required|integer|min:0',
            'corrections.*.keterangan' => 'nullable|string|max:255',
            'tanggal_opname' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $validated = $validator->validated();
            $corrections = $validated['corrections'];
            $tanggalOpname = $validated['tanggal_opname'];
            $results = [];

            foreach ($corrections as $correction) {
                $masterBarang = MasterBarangModel::where('id_barang', $correction['id_barang'])->first();

                if (!$masterBarang) {
                    throw new \Exception("Barang dengan ID {$correction['id_barang']} tidak ditemukan");
                }

                $stok_isi_sistem = $masterBarang->stok_tabung_isi;
                $stok_kosong_sistem = $masterBarang->stok_tabung_kosong;

                $stok_isi_fisik = $correction['stok_isi_fisik'];
                $stok_kosong_fisik = $correction['stok_kosong_fisik'];

                // Hitung selisih (koreksi)
                $selisih_isi = $stok_isi_fisik - $stok_isi_sistem;
                $selisih_kosong = $stok_kosong_fisik - $stok_kosong_sistem;

                // Skip jika tidak ada selisih
                if ($selisih_isi == 0 && $selisih_kosong == 0) {
                    continue;
                }

                // Update stok di master barang sesuai hasil fisik
                $masterBarang->update([
                    'stok_tabung_isi' => $stok_isi_fisik,
                    'stok_tabung_kosong' => $stok_kosong_fisik
                ]);

                // Catat riwayat stok koreksi
                $riwayatStok = RiwayatStokModel::create([
                    'id_barang' => $correction['id_barang'],
                    'id_transaksi' => null, // Tidak ada transaksi spesifik untuk koreksi
                    'tipe_transaksi' => 'KOREKSI',
                    'perubahan_isi' => $selisih_isi,
                    'perubahan_kosong' => $selisih_kosong,
                    'stok_isi_setelah' => $stok_isi_fisik,
                    'stok_kosong_setelah' => $stok_kosong_fisik,
                    'tanggal_transaksi' => $tanggalOpname
                ]);

                $results[] = [
                    'id_barang' => $correction['id_barang'],
                    'nama_barang' => $masterBarang->nama_barang,
                    'stok_sistem' => [
                        'tabung_isi' => $stok_isi_sistem,
                        'tabung_kosong' => $stok_kosong_sistem
                    ],
                    'stok_fisik' => [
                        'tabung_isi' => $stok_isi_fisik,
                        'tabung_kosong' => $stok_kosong_fisik
                    ],
                    'selisih' => [
                        'tabung_isi' => $selisih_isi,
                        'tabung_kosong' => $selisih_kosong
                    ],
                    'keterangan' => $correction['keterangan'] ?? null,
                    'id_riwayat' => $riwayatStok->id_riwayat
                ];
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stok opname berhasil dilakukan',
                'data' => [
                    'tanggal_opname' => $tanggalOpname,
                    'total_koreksi' => count($results),
                    'detail_koreksi' => $results
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error melakukan stok opname: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan laporan selisih stok
     */
    public function laporanStok(Request $request)
    {
        try {
            $query = RiwayatStokModel::with('barang')
                ->whereIn('tipe_transaksi', ['KELUAR', 'MASUK']);

            // Filter tanggal jika disediakan
            if ($request->has('tanggal_dari') && $request->has('tanggal_sampai')) {
                $query->whereBetween('tanggal_transaksi', [
                    $request->tanggal_dari,
                    $request->tanggal_sampai
                ]);
            }

            // Filter barang jika disediakan
            if ($request->has('id_barang')) {
                $query->where('id_barang', $request->id_barang);
            }

            $laporanSelisih = $query->orderBy('tanggal_transaksi', 'desc')
                ->paginate(15);

            // Hitung summary
            $total_keluar_isi = RiwayatStokModel::where('tipe_transaksi', 'KELUAR')
                ->when($request->has('tanggal_dari') && $request->has('tanggal_sampai'), function ($q) use ($request) {
                    $q->whereBetween('tanggal_transaksi', [$request->tanggal_dari, $request->tanggal_sampai]);
                })
                ->when($request->has('id_barang'), function ($q) use ($request) {
                    $q->where('id_barang', $request->id_barang);
                })
                ->sum('perubahan_isi');

            $total_keluar_kosong = RiwayatStokModel::where('tipe_transaksi', 'KELUAR')
                ->when($request->has('tanggal_dari') && $request->has('tanggal_sampai'), function ($q) use ($request) {
                    $q->whereBetween('tanggal_transaksi', [$request->tanggal_dari, $request->tanggal_sampai]);
                })
                ->when($request->has('id_barang'), function ($q) use ($request) {
                    $q->where('id_barang', $request->id_barang);
                })
                ->sum('perubahan_kosong');

            $total_masuk_isi = RiwayatStokModel::where('tipe_transaksi', 'MASUK')
                ->when($request->has('tanggal_dari') && $request->has('tanggal_sampai'), function ($q) use ($request) {
                    $q->whereBetween('tanggal_transaksi', [$request->tanggal_dari, $request->tanggal_sampai]);
                })
                ->when($request->has('id_barang'), function ($q) use ($request) {
                    $q->where('id_barang', $request->id_barang);
                })
                ->sum('perubahan_isi');

            $total_masuk_kosong = RiwayatStokModel::where('tipe_transaksi', 'MASUK')
                ->when($request->has('tanggal_dari') && $request->has('tanggal_sampai'), function ($q) use ($request) {
                    $q->whereBetween('tanggal_transaksi', [$request->tanggal_dari, $request->tanggal_sampai]);
                })
                ->when($request->has('id_barang'), function ($q) use ($request) {
                    $q->where('id_barang', $request->id_barang);
                })
                ->sum('perubahan_kosong');

            $id_riwayat = $request->input('id_riwayat');



            $riwayat = RiwayatStokModel::find($id_riwayat);

            $stok_isi_sebelum = $riwayat ? ($riwayat->stok_isi_setelah - $riwayat->perubahan_isi) : null;
            $stok_kosong_sebelum = $riwayat ? ($riwayat->stok_kosong_setelah - $riwayat->perubahan_kosong) : null;


            $summary = [
                'stok_isi_awal' => $stok_isi_sebelum ?? 0,
                'stok_kosong_awal' => $stok_kosong_sebelum ?? 0,
                'total_transaksi' => $laporanSelisih->total(),
                'total_keluar_isi' => $total_keluar_isi,
                'total_keluar_kosong' => $total_keluar_kosong,
                'total_masuk_isi' => $total_masuk_isi,
                'total_masuk_kosong' => $total_masuk_kosong
            ];

            return response()->json([
                'status' => true,
                'message' => 'Laporan selisih stok berhasil diambil',
                'summary' => $summary,
                'data' => $laporanSelisih
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating laporan selisih',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan barang dengan stok minimum
     */
    public function stokMinimum()
    {
        try {
            $stokMinimum = MasterBarangModel::whereRaw('stok_tabung_isi <= stok_minimum')
                ->orWhereRaw('stok_tabung_kosong <= stok_minimum')
                ->select([
                    'id_barang',
                    'kode_barang',
                    'nama_barang',
                    'kapasitas',
                    'stok_tabung_isi',
                    'stok_tabung_kosong',
                    'stok_minimum'
                ])
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Data barang dengan stok minimum berhasil diambil',
                'total_items' => $stokMinimum->count(),
                'data' => $stokMinimum
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching stok minimum',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
