<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MasterBarangModel;
use App\Models\RiwayatStokModel;
use App\Models\StokOpnameModel; // Tambahkan model baru
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StokOpnameController extends Controller
{
    /**
     * Tampilkan data stok opname
     */
    public function index(Request $request)
    {
        try {
            $query = StokOpnameModel::with('barang');

            // Filter by id_barang
            if ($request->filled('id_barang')) {
                $query->where('id_barang', $request->id_barang);
            }

            // Filter by tanggal
            if ($request->filled('tanggal_dari') && $request->filled('tanggal_sampai')) {
                $query->whereBetween('tanggal_opname', [
                    $request->tanggal_dari,
                    $request->tanggal_sampai
                ]);
            }

            // Sorting
            $sortBy = $request->input('sortby', 'tanggal_opname');
            $sortOrder = $request->input('sortorder', 'desc');
            $allowedSortColumns = [
                'id_opname',
                'id_barang',
                'tanggal_opname',
                'selisih_isi',
                'selisih_kosong'
            ];

            if (in_array($sortBy, $allowedSortColumns)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('tanggal_opname', 'desc');
            }

            $stokOpname = $query->paginate($request->input('per_page', 15));

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
     * PENTING: Method ini SELALU mengambil stok TERBARU dari master barang
     * Bukan dari data opname terakhir, agar koreksi kedua/ketiga dst selalu akurat
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

            // Tambahkan informasi opname terakhir untuk setiap barang
            $data = $masterBarang->map(function ($barang) {
                $lastOpname = StokOpnameModel::where('id_barang', $barang->id_barang)
                    ->orderBy('id_opname', 'desc')
                    ->first();

                return [
                    'id_barang' => $barang->id_barang,
                    'kode_barang' => $barang->kode_barang,
                    'nama_barang' => $barang->nama_barang,
                    'kapasitas' => $barang->kapasitas,
                    'stok_tabung_isi' => $barang->stok_tabung_isi,        // STOK REAL-TIME SAAT INI
                    'stok_tabung_kosong' => $barang->stok_tabung_kosong,  // STOK REAL-TIME SAAT INI
                    'stok_minimum' => $barang->stok_minimum,
                    'last_opname' => $lastOpname ? [
                        'tanggal' => $lastOpname->tanggal_opname,
                        'created_by' => $lastOpname->created_by,
                        'keterangan' => $lastOpname->keterangan,
                    ] : null
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Data stok REAL-TIME saat ini berhasil diambil',
                'data' => $data
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
     * Get detail barang untuk form opname (saat pilih barang di dropdown)
     * Endpoint: GET /api/stok-opname/detail-barang/{id_barang}
     */
    public function getDetailBarang($id_barang)
    {
        try {
            $barang = MasterBarangModel::find($id_barang);

            if (!$barang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Barang tidak ditemukan'
                ], 404);
            }

            // Ambil opname terakhir untuk referensi
            $lastOpname = StokOpnameModel::where('id_barang', $id_barang)
                ->orderBy('id_opname', 'desc')
                ->first();

            return response()->json([
                'status' => true,
                'message' => 'Detail barang berhasil diambil',
                'data' => [
                    'id_barang' => $barang->id_barang,
                    'kode_barang' => $barang->kode_barang,
                    'nama_barang' => $barang->nama_barang,
                    'kapasitas' => $barang->kapasitas,
                    'stok_sistem_isi' => $barang->stok_tabung_isi,      // Auto-fill ke form
                    'stok_sistem_kosong' => $barang->stok_tabung_kosong, // Auto-fill ke form
                    'last_opname' => $lastOpname ? [
                        'tanggal' => $lastOpname->tanggal_opname,
                        'created_by' => $lastOpname->created_by
                    ] : null
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lakukan koreksi stok (Stok Opname)
     * 
     * PENTING UNTUK FRONTEND:
     * - Sebelum koreksi, WAJIB panggil GET /api/stok-opname/current-stok
     * - Jangan ambil data dari laporan opname lama
     * - Gunakan stok_tabung_isi dan stok_tabung_kosong dari response current-stok
     * - Ini memastikan koreksi kedua/ketiga menggunakan stok TERBARU
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
            $createdBy = Auth::user()->name ?? 'System'; // Ambil user yang login

            foreach ($corrections as $correction) {
                $masterBarang = MasterBarangModel::where('id_barang', $correction['id_barang'])->first();

                if (!$masterBarang) {
                    throw new \Exception("Barang dengan ID {$correction['id_barang']} tidak ditemukan");
                }

                // Ambil stok sistem saat ini SEBELUM ada perubahan apapun
                $stok_isi_sistem = $masterBarang->stok_tabung_isi;
                $stok_kosong_sistem = $masterBarang->stok_tabung_kosong;

                // VALIDASI: Pastikan stok sistem tidak null atau kosong
                if ($stok_isi_sistem === null) {
                    throw new \Exception("Stok isi sistem untuk barang '{$masterBarang->nama_barang}' tidak valid (NULL). Silakan periksa data master barang.");
                }

                if ($stok_kosong_sistem === null) {
                    throw new \Exception("Stok kosong sistem untuk barang '{$masterBarang->nama_barang}' tidak valid (NULL). Silakan periksa data master barang.");
                }

                // Ambil stok fisik dari input
                $stok_isi_fisik = $correction['stok_isi_fisik'];
                $stok_kosong_fisik = $correction['stok_kosong_fisik'];

                // VALIDASI: Pastikan input stok fisik valid
                if (!isset($correction['stok_isi_fisik']) || $correction['stok_isi_fisik'] === null || $correction['stok_isi_fisik'] === '') {
                    throw new \Exception("Stok isi fisik untuk barang '{$masterBarang->nama_barang}' wajib diisi dan tidak boleh kosong.");
                }

                if (!isset($correction['stok_kosong_fisik']) || $correction['stok_kosong_fisik'] === null || $correction['stok_kosong_fisik'] === '') {
                    throw new \Exception("Stok kosong fisik untuk barang '{$masterBarang->nama_barang}' wajib diisi dan tidak boleh kosong.");
                }

                // Log untuk debug - SEBELUM proses apapun
                Log::info('Koreksi Stok - Data Input', [
                    'id_barang' => $correction['id_barang'],
                    'nama_barang' => $masterBarang->nama_barang,
                    'stok_isi_sistem' => $stok_isi_sistem,
                    'stok_kosong_sistem' => $stok_kosong_sistem,
                    'stok_isi_fisik_input' => $stok_isi_fisik,
                    'stok_kosong_fisik_input' => $stok_kosong_fisik,
                    'raw_correction' => $correction,
                ]);

                // Hitung selisih
                $selisih_isi = $stok_isi_fisik - $stok_isi_sistem;
                $selisih_kosong = $stok_kosong_fisik - $stok_kosong_sistem;

                // Skip jika tidak ada selisih
                if ($selisih_isi == 0 && $selisih_kosong == 0) {
                    Log::info('Koreksi Stok - SKIP (tidak ada selisih)', [
                        'id_barang' => $correction['id_barang'],
                        'nama_barang' => $masterBarang->nama_barang,
                    ]);
                    continue;
                }

                // PENTING: Simpan ke dbo_stok_opname SEBELUM update master barang
                // Agar stok_sistem yang tersimpan adalah stok SEBELUM dikoreksi
                $dataToSave = [
                    'id_barang' => $correction['id_barang'],
                    'tanggal_opname' => $tanggalOpname,
                    'stok_isi_sistem' => $stok_isi_sistem,
                    'stok_kosong_sistem' => $stok_kosong_sistem,
                    'stok_isi_fisik' => $stok_isi_fisik,
                    'stok_kosong_fisik' => $stok_kosong_fisik,
                    'selisih_isi' => $selisih_isi,
                    'selisih_kosong' => $selisih_kosong,
                    'keterangan' => $correction['keterangan'] ?? null,
                    'created_by' => $createdBy
                ];

                // Log data yang akan disimpan
                Log::info('Koreksi Stok - Data SEBELUM Simpan ke DB', $dataToSave);

                $stokOpname = StokOpnameModel::create($dataToSave);

                // Log data yang tersimpan
                Log::info('Koreksi Stok - Data SETELAH Simpan ke DB', [
                    'id_opname' => $stokOpname->id_opname,
                    'stok_isi_sistem_tersimpan' => $stokOpname->stok_isi_sistem,
                    'stok_kosong_sistem_tersimpan' => $stokOpname->stok_kosong_sistem,
                    'stok_isi_fisik_tersimpan' => $stokOpname->stok_isi_fisik,
                    'stok_kosong_fisik_tersimpan' => $stokOpname->stok_kosong_fisik,
                ]);

                // VALIDASI: Pastikan data tersimpan dengan benar
                if ($stokOpname->stok_isi_sistem != $stok_isi_sistem || $stokOpname->stok_kosong_sistem != $stok_kosong_sistem) {
                    throw new \Exception("ERROR: Data stok sistem tidak tersimpan dengan benar! Seharusnya Isi={$stok_isi_sistem}, Kosong={$stok_kosong_sistem}, tapi tersimpan Isi={$stokOpname->stok_isi_sistem}, Kosong={$stokOpname->stok_kosong_sistem}");
                }

                if ($stokOpname->stok_isi_fisik != $stok_isi_fisik || $stokOpname->stok_kosong_fisik != $stok_kosong_fisik) {
                    throw new \Exception("ERROR: Data stok fisik tidak tersimpan dengan benar! Seharusnya Isi={$stok_isi_fisik}, Kosong={$stok_kosong_fisik}, tapi tersimpan Isi={$stokOpname->stok_isi_fisik}, Kosong={$stokOpname->stok_kosong_fisik}");
                }

                // Sekarang update stok di master barang dengan nilai fisik
                $masterBarang->update([
                    'stok_tabung_isi' => $stok_isi_fisik,
                    'stok_tabung_kosong' => $stok_kosong_fisik
                ]);

                Log::info('Koreksi Stok - Master Barang Diupdate', [
                    'id_barang' => $correction['id_barang'],
                    'stok_baru_isi' => $stok_isi_fisik,
                    'stok_baru_kosong' => $stok_kosong_fisik,
                ]);

                // // Catat riwayat stok untuk audit trail
                // RiwayatStokModel::create([
                //     'id_barang' => $correction['id_barang'],
                //     'tanggal_transaksi' => $tanggalOpname,
                //     'tipe_transaksi' => 'KOREKSI',
                //     'jumlah_masuk_isi' => $selisih_isi > 0 ? $selisih_isi : 0,
                //     'jumlah_masuk_kosong' => $selisih_kosong > 0 ? $selisih_kosong : 0,
                //     'jumlah_keluar_isi' => $selisih_isi < 0 ? abs($selisih_isi) : 0,
                //     'jumlah_keluar_kosong' => $selisih_kosong < 0 ? abs($selisih_kosong) : 0,
                //     'stok_akhir_isi' => $stok_isi_fisik,
                //     'stok_akhir_kosong' => $stok_kosong_fisik,
                //     'keterangan' => 'Koreksi Stok Opname - ' . ($correction['keterangan'] ?? 'Penyesuaian stok fisik'),
                //     'created_by' => $createdBy
                // ]);


                $results[] = [
                    'id_opname' => $stokOpname->id_opname,
                    'id_barang' => $correction['id_barang'],
                    'nama_barang' => $masterBarang->nama_barang,
                    'stok_sistem' => [
                        'isi' => $stok_isi_sistem,
                        'kosong' => $stok_kosong_sistem
                    ],
                    'stok_fisik' => [
                        'isi' => $stok_isi_fisik,
                        'kosong' => $stok_kosong_fisik
                    ],
                    'selisih' => [
                        'isi' => $selisih_isi,
                        'kosong' => $selisih_kosong
                    ],
                    'keterangan' => $correction['keterangan'] ?? null
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
     * Tampilkan laporan stok opname
     * 
     * KONSEP BARU:
     * - Menampilkan SEMUA record opname (bukan hanya terakhir per barang)
     * - Setiap koreksi akan muncul sebagai baris terpisah
     * - Bisa filter by id_barang untuk lihat history 1 barang
     * - Sorting by id_opname DESC (terbaru di atas)
     */
    public function laporanStok(Request $request)
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $currentPage = (int) $request->input('page', 1);

            // Query semua data opname dengan join ke master barang
            $queryOpname = StokOpnameModel::with('barang')
                ->select('dbo_stok_opname.*');

            // Filter by id_barang jika ada
            if ($request->filled('id_barang')) {
                $queryOpname->where('id_barang', $request->id_barang);
            }

            // Filter by tanggal
            if ($request->filled('tanggal_dari') && $request->filled('tanggal_sampai')) {
                $queryOpname->whereBetween('tanggal_opname', [
                    $request->tanggal_dari,
                    $request->tanggal_sampai
                ]);
            }

            // Filter by nama barang (join)
            if ($request->filled('nama_barang')) {
                $queryOpname->whereHas('barang', function ($q) use ($request) {
                    $q->where('nama_barang', 'LIKE', '%' . $request->nama_barang . '%');
                });
            }

            // Sorting terbaru di atas
            $queryOpname->orderBy('id_opname', 'desc');

            // Get all data untuk summary
            $allOpname = $queryOpname->get();

            // Format response
            $items = [];
            $totalSelisihIsi = 0;
            $totalSelisihKosong = 0;
            $totalStokSistemIsi = 0;
            $totalStokSistemKosong = 0;
            $totalStokFisikIsi = 0;
            $totalStokFisikKosong = 0;

            foreach ($allOpname as $opname) {
                $items[] = [
                    'id_opname' => $opname->id_opname,
                    'id_barang' => $opname->id_barang,
                    'kode_barang' => $opname->barang->kode_barang ?? '-',
                    'nama_barang' => $opname->barang->nama_barang ?? '-',
                    'kapasitas' => $opname->barang->kapasitas ?? '-',
                    'tanggal_opname' => $opname->tanggal_opname,
                    'stok_sistem' => [
                        'isi' => $opname->stok_isi_sistem,
                        'kosong' => $opname->stok_kosong_sistem
                    ],
                    'stok_fisik' => [
                        'isi' => $opname->stok_isi_fisik,
                        'kosong' => $opname->stok_kosong_fisik
                    ],
                    'selisih' => [
                        'isi' => $opname->selisih_isi,
                        'kosong' => $opname->selisih_kosong
                    ],
                    'keterangan' => $opname->keterangan ?? '-',
                    'created_by' => $opname->created_by ?? 'System',
                    'created_at' => $opname->created_at
                ];

                // Hitung total untuk summary
                $totalSelisihIsi += (float) ($opname->selisih_isi ?? 0);
                $totalSelisihKosong += (float) ($opname->selisih_kosong ?? 0);
                $totalStokSistemIsi += (float) ($opname->stok_isi_sistem ?? 0);
                $totalStokSistemKosong += (float) ($opname->stok_kosong_sistem ?? 0);
                $totalStokFisikIsi += (float) ($opname->stok_isi_fisik ?? 0);
                $totalStokFisikKosong += (float) ($opname->stok_kosong_fisik ?? 0);
            }

            // Pagination manual
            $totalItems = count($items);
            $totalPages = ceil($totalItems / $perPage);
            $offset = ($currentPage - 1) * $perPage;
            $itemsPaginated = array_slice($items, $offset, $perPage);

            // Hitung jumlah barang unik yang sudah opname
            $uniqueBarang = $allOpname->unique('id_barang')->count();

            $response = [
                'status' => true,
                'message' => 'Laporan stok opname berhasil diambil (semua record ditampilkan)',
                'summary' => [
                    'periode' => [
                        'tanggal_dari' => $request->input('tanggal_dari', 'Semua'),
                        'tanggal_sampai' => $request->input('tanggal_sampai', 'Semua')
                    ],
                    'total_record_opname' => $totalItems,
                    'total_barang_pernah_opname' => $uniqueBarang,
                    'total_stok_sistem' => [
                        'isi' => $totalStokSistemIsi,
                        'kosong' => $totalStokSistemKosong
                    ],
                    'total_stok_fisik' => [
                        'isi' => $totalStokFisikIsi,
                        'kosong' => $totalStokFisikKosong
                    ],
                    'total_selisih' => [
                        'isi' => $totalSelisihIsi,
                        'kosong' => $totalSelisihKosong
                    ]
                ],
                'data' => [
                    'current_page' => $currentPage,
                    'data' => $itemsPaginated,
                    'per_page' => $perPage,
                    'total' => $totalItems,
                    'total_page' => $totalPages,
                    'from' => $totalItems > 0 ? $offset + 1 : null,
                    'to' => $totalItems > 0 ? min($offset + $perPage, $totalItems) : null,
                    'has_next_page' => $currentPage < $totalPages,
                    'has_prev_page' => $currentPage > 1
                ]
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan history opname untuk barang tertentu
     * Endpoint: GET /api/stok-opname/history/{id_barang}
     */
    public function historyOpname($id_barang)
    {
        try {
            $barang = MasterBarangModel::find($id_barang);

            if (!$barang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Barang tidak ditemukan'
                ], 404);
            }

            $history = StokOpnameModel::where('id_barang', $id_barang)
                ->orderBy('id_opname', 'desc')
                ->get()
                ->map(function ($opname) {
                    return [
                        'id_opname' => $opname->id_opname,
                        'tanggal_opname' => $opname->tanggal_opname,
                        'stok_sistem' => [
                            'isi' => $opname->stok_isi_sistem,
                            'kosong' => $opname->stok_kosong_sistem
                        ],
                        'stok_fisik' => [
                            'isi' => $opname->stok_isi_fisik,
                            'kosong' => $opname->stok_kosong_fisik
                        ],
                        'selisih' => [
                            'isi' => $opname->selisih_isi,
                            'kosong' => $opname->selisih_kosong
                        ],
                        'keterangan' => $opname->keterangan,
                        'created_by' => $opname->created_by,
                        'created_at' => $opname->created_at
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'History opname berhasil diambil',
                'barang' => [
                    'id_barang' => $barang->id_barang,
                    'nama_barang' => $barang->nama_barang,
                    'stok_saat_ini' => [
                        'isi' => $barang->stok_tabung_isi,
                        'kosong' => $barang->stok_tabung_kosong
                    ]
                ],
                'total_history' => $history->count(),
                'history' => $history
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching history: ' . $e->getMessage()
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

    /**
     * Hapus data stok opname
     */
    public function destroy($id_opname)
    {
        $stokOpname = StokOpnameModel::find($id_opname);

        if (!$stokOpname) {
            return response()->json([
                'status' => false,
                'message' => 'Data stok opname tidak ditemukan'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $masterBarang = MasterBarangModel::find($stokOpname->id_barang);

            if (!$masterBarang) {
                throw new \Exception("Barang dengan ID {$stokOpname->id_barang} tidak ditemukan");
            }

            // Kembalikan stok ke kondisi sistem sebelumnya
            $masterBarang->update([
                'stok_tabung_isi' => $stokOpname->stok_isi_sistem,
                'stok_tabung_kosong' => $stokOpname->stok_kosong_sistem
            ]);

            // Hapus riwayat stok terkait (opsional)
            RiwayatStokModel::where('id_barang', $stokOpname->id_barang)
                ->where('tanggal_transaksi', $stokOpname->tanggal_opname)
                ->where('tipe_transaksi', 'KOREKSI')
                ->delete();

            $deletedData = [
                'id_opname' => $stokOpname->id_opname,
                'id_barang' => $stokOpname->id_barang,
                'nama_barang' => $masterBarang->nama_barang,
                'tanggal_opname' => $stokOpname->tanggal_opname,
                'stok_dikembalikan_ke' => [
                    'tabung_isi' => $stokOpname->stok_isi_sistem,
                    'tabung_kosong' => $stokOpname->stok_kosong_sistem
                ]
            ];

            $stokOpname->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data stok opname berhasil dihapus dan stok telah dikembalikan',
                'data' => $deletedData
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error menghapus stok opname: ' . $e->getMessage()
            ], 500);
        }
    }
}
