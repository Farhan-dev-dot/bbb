<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BarangMasukModel;
use App\Models\BarangKeluarModel;
use App\Models\DboTransaksiModel;
use App\Models\MasterBarangModel;
use App\Models\RiwayatStokModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    /**
     * Laporan Mutasi Stok Harian
     */
    public function mutasiStokHarian(Request $request)
    {
        try {
            $tanggal = $request->get('tanggal', now()->format('Y-m-d'));
            $idBarang = $request->get('id_barang');

            $query = RiwayatStokModel::with('barang')
                ->whereDate('tanggal_transaksi', $tanggal);

            if ($idBarang) {
                $query->where('id_barang', $idBarang);
            }

            $mutasiStok = $query->orderBy('tanggal_transaksi', 'asc')->get();

            // Group by barang
            $laporanPerBarang = $mutasiStok->groupBy('id_barang')->map(function ($items, $idBarang) {
                $barang = $items->first()->barang;
                $transaksi = $items->map(function ($item) {
                    return [
                        'tipe_transaksi' => $item->tipe_transaksi,
                        'perubahan_isi' => $item->perubahan_isi,
                        'perubahan_kosong' => $item->perubahan_kosong,
                        'stok_isi_setelah' => $item->stok_isi_setelah,
                        'stok_kosong_setelah' => $item->stok_kosong_setelah,
                        'tanggal_transaksi' => $item->tanggal_transaksi
                    ];
                });

                return [
                    'barang' => [
                        'id_barang' => $barang->id_barang,
                        'kode_barang' => $barang->kode_barang,
                        'nama_barang' => $barang->nama_barang
                    ],
                    'transaksi' => $transaksi,
                    'total_masuk_isi' => $items->where('tipe_transaksi', 'MASUK')->sum('perubahan_isi'),
                    'total_keluar_isi' => abs($items->where('tipe_transaksi', 'KELUAR')->sum('perubahan_isi')),
                    'total_masuk_kosong' => $items->where('tipe_transaksi', 'KELUAR')->sum('perubahan_kosong'),
                    'total_koreksi_isi' => $items->where('tipe_transaksi', 'KOREKSI')->sum('perubahan_isi'),
                    'total_koreksi_kosong' => $items->where('tipe_transaksi', 'KOREKSI')->sum('perubahan_kosong'),
                    'stok_isi_akhir' => $items->last()->stok_isi_setelah,
                    'stok_kosong_akhir' => $items->last()->stok_kosong_setelah
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Laporan mutasi stok harian berhasil diambil',
                'tanggal' => $tanggal,
                'total_transaksi' => $mutasiStok->count(),
                'data' => $laporanPerBarang->values()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating laporan mutasi stok',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Laporan Mutasi Stok Bulanan
     */
    public function mutasiStokBulanan(Request $request)
    {
        try {
            $bulan = $request->get('bulan', now()->format('m'));
            $tahun = $request->get('tahun', now()->format('Y'));
            $idBarang = $request->get('id_barang');

            $query = RiwayatStokModel::with('barang')
                ->whereMonth('tanggal_transaksi', $bulan)
                ->whereYear('tanggal_transaksi', $tahun);

            if ($idBarang) {
                $query->where('id_barang', $idBarang);
            }

            $mutasiStok = $query->orderBy('tanggal_transaksi', 'asc')->get();

            // Summary per barang untuk satu bulan
            $summary = $mutasiStok->groupBy('id_barang')->map(function ($items, $idBarang) {
                $barang = $items->first()->barang;

                return [
                    'barang' => [
                        'id_barang' => $barang->id_barang,
                        'kode_barang' => $barang->kode_barang,
                        'nama_barang' => $barang->nama_barang
                    ],
                    'total_transaksi' => $items->count(),
                    'total_masuk_isi' => $items->where('tipe_transaksi', 'MASUK')->sum('perubahan_isi'),
                    'total_keluar_isi' => abs($items->where('tipe_transaksi', 'KELUAR')->sum('perubahan_isi')),
                    'total_masuk_kosong' => $items->where('tipe_transaksi', 'KELUAR')->sum('perubahan_kosong'),
                    'total_koreksi_isi' => $items->where('tipe_transaksi', 'KOREKSI')->sum('perubahan_isi'),
                    'total_koreksi_kosong' => $items->where('tipe_transaksi', 'KOREKSI')->sum('perubahan_kosong'),
                    'stok_isi_akhir' => $items->last()->stok_isi_setelah,
                    'stok_kosong_akhir' => $items->last()->stok_kosong_setelah
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Laporan mutasi stok bulanan berhasil diambil',
                'periode' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun
                ],
                'total_transaksi' => $mutasiStok->count(),
                'data' => $summary->values()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating laporan mutasi stok bulanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Laporan Barang Masuk
     */
    public function laporanBarangMasuk(Request $request)
    {
        try {
            $tanggalDari = $request->get('tanggal_dari');
            $tanggalSampai = $request->get('tanggal_sampai');
            $supplier = $request->get('supplier');

            $query = BarangMasukModel::with('barang');

            if ($tanggalDari && $tanggalSampai) {
                $query->whereBetween('tanggal_masuk', [$tanggalDari, $tanggalSampai]);
            }

            if ($supplier) {
                $query->where('supplier', 'like', '%' . $supplier . '%');
            }

            $barangMasuk = $query->orderBy('tanggal_masuk', 'desc')->paginate(20);

            // Summary - Fixed GROUP BY issue
            $baseQuery = BarangMasukModel::query();

            if ($tanggalDari && $tanggalSampai) {
                $baseQuery->whereBetween('tanggal_masuk', [$tanggalDari, $tanggalSampai]);
            }

            if ($supplier) {
                $baseQuery->where('supplier', 'like', '%' . $supplier . '%');
            }

            $summary = [
                'total_transaksi' => $baseQuery->count(),
                'total_tabung_isi_masuk' => $baseQuery->sum('jumlah_isi'),
                'total_tabung_kosong_masuk' => $baseQuery->sum('jumlah_kosong'),
                'supplier_terbanyak' => BarangMasukModel::select('supplier', DB::raw('COUNT(*) as total_transaksi'))
                    ->whereNotNull('supplier')
                    ->when($tanggalDari && $tanggalSampai, function ($q) use ($tanggalDari, $tanggalSampai) {
                        return $q->whereBetween('tanggal_masuk', [$tanggalDari, $tanggalSampai]);
                    })
                    ->when($supplier, function ($q) use ($supplier) {
                        return $q->where('supplier', 'like', '%' . $supplier . '%');
                    })
                    ->groupBy('supplier')
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit(5)
                    ->get()
                    ->pluck('supplier')
            ];

            return response()->json([
                'status' => true,
                'message' => 'Laporan barang masuk berhasil diambil',
                'summary' => $summary,
                'data' => $barangMasuk
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating laporan barang masuk',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Laporan Barang Keluar/Penjualan
     */
    public function laporanBarangKeluar(Request $request)
    {
        try {
            $tanggalDari = $request->get('tanggal_dari');
            $tanggalSampai = $request->get('tanggal_sampai');
            $idCustomer = $request->get('id_customer');

            $query = BarangKeluarModel::with(['barang', 'customer']);

            if ($tanggalDari && $tanggalSampai) {
                $query->whereBetween('tanggal_keluar', [$tanggalDari, $tanggalSampai]);
            }

            if ($idCustomer) {
                $query->where('id_customer', $idCustomer);
            }

            $barangKeluar = $query->orderBy('tanggal_keluar', 'desc')->paginate(20);


            $summary = [
                'total_transaksi' => $barangKeluar->total(),
                'total_tabung_isi_keluar' => $query->sum('jumlah_isi'),
                'total_tabung_kosong_masuk' => $query->sum('jumlah_kosong'),
                'total_pinjam_tabung' => $query->sum('pinjam_tabung'),
                'total_pendapatan' => $query->sum('total_harga'),
                'rata_rata_harga' => $query->avg('harga_satuan')
            ];

            return response()->json([
                'status' => true,
                'message' => 'Laporan barang keluar berhasil diambil',
                'summary' => $summary,
                'data' => $barangKeluar
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating laporan barang keluar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Laporan Cashflow
     */
    public function laporanCashflow(Request $request)
    {
        try {
            $tanggalDari = $request->get('tanggal_dari', now()->subMonth()->format('Y-m-d'));
            $tanggalSampai = $request->get('tanggal_sampai', now()->format('Y-m-d'));

            // Pendapatan dari penjualan
            $pendapatan = BarangKeluarModel::whereBetween('tanggal_keluar', [$tanggalDari, $tanggalSampai])
                ->where('status', 'completed')
                ->sum('total_harga');

            // Breakdown pendapatan per hari
            $pendapatanHarian = BarangKeluarModel::whereBetween('tanggal_keluar', [$tanggalDari, $tanggalSampai])
                ->where('status', 'completed')
                ->selectRaw('DATE(tanggal_keluar) as tanggal, SUM(total_harga) as total_pendapatan, COUNT(*) as total_transaksi')
                ->groupBy(DB::raw('DATE(tanggal_keluar)'))
                ->orderBy('tanggal', 'asc')
                ->get();

            // Top 5 customer berdasarkan pembelian
            $topCustomer = BarangKeluarModel::with('customer')
                ->whereBetween('tanggal_keluar', [$tanggalDari, $tanggalSampai])
                ->where('status', 'completed')
                ->selectRaw('id_customer, SUM(total_harga) as total_pembelian, COUNT(*) as total_transaksi')
                ->groupBy('id_customer')
                ->orderBy('total_pembelian', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Laporan cashflow berhasil diambil',
                'periode' => [
                    'tanggal_dari' => $tanggalDari,
                    'tanggal_sampai' => $tanggalSampai
                ],
                'summary' => [
                    'total_pendapatan' => $pendapatan,
                    'rata_rata_pendapatan_harian' => $pendapatanHarian->avg('total_pendapatan'),
                    'hari_terbaik' => $pendapatanHarian->sortByDesc('total_pendapatan')->first()
                ],
                'pendapatan_harian' => $pendapatanHarian,
                'top_customer' => $topCustomer
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating laporan cashflow',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard Summary untuk Laporan
     */
    public function dashboardSummary()
    {
        try {
            // Stok hari ini
            $stokHariIni = MasterBarangModel::selectRaw('
                SUM(stok_tabung_isi) as total_stok_isi,
                SUM(stok_tabung_kosong) as total_stok_kosong,
                COUNT(*) as total_jenis_barang
            ')->first();

            // Transaksi hari ini
            $transaksiHariIni = [
                'barang_masuk' => BarangMasukModel::whereDate('tanggal_masuk', now())->count(),
                'barang_keluar' => BarangKeluarModel::whereDate('tanggal_keluar', now())->count(),
                'pendapatan' => BarangKeluarModel::whereDate('tanggal_keluar', now())
                    ->where('status', 'completed')
                    ->sum('total_harga')
            ];

            // Barang dengan stok minimum
            $stokMinimum = MasterBarangModel::whereRaw('stok_tabung_isi <= stok_minimum')
                ->orWhereRaw('stok_tabung_kosong <= stok_minimum')
                ->count();

            // Riwayat transaksi terakhir
            $transaksiTerakhir = RiwayatStokModel::with('barang')
                ->orderBy('tanggal_transaksi', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Dashboard summary berhasil diambil',
                'data' => [
                    'stok_summary' => $stokHariIni,
                    'transaksi_hari_ini' => $transaksiHariIni,
                    'alert' => [
                        'stok_minimum' => $stokMinimum
                    ],
                    'transaksi_terakhir' => $transaksiTerakhir
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating dashboard summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function LaporanTransaksi(Request $request)
    {
        try {
            // Query dengan JOIN untuk mendapatkan semua data
            $query = DB::table('dbo_transaksi as t')
                ->leftJoin('dbo_barang_keluar as bk', 't.id_transaksi', '=', 'bk.id_transaksi')
                ->leftJoin('dbo_customer as c', 'bk.id_customer', '=', 'c.id_customer')
                ->leftJoin('dbo_master_barang as mb', 'bk.id_barang', '=', 'mb.id_barang')
                ->select(
                    // Transaksi fields
                    't.id_transaksi',
                    't.no_transaksi',
                    't.tanggal_transaksi',
                    't.jenis_transaksi',
                    't.jumlah_tabung_isi',
                    't.jumlah_tabung_kosong',
                    't.jumlah_pinjam_tabung',
                    't.total_harga',
                    't.metode_pembayaran',
                    't.alamat_pengiriman',
                    't.nama_pengirim',
                    't.keterangan',
                    // Barang keluar fields
                    'bk.id_keluar',
                    'bk.harga_satuan',
                    'bk.tanggal_keluar',
                    'bk.total_harga as total_harga_detail',
                    'bk.jumlah_isi',
                    'bk.jumlah_kosong',
                    // Customer fields
                    'c.id_customer',
                    'c.nama_customer',
                    'c.alamat',
                    'c.telepon',
                    'c.email',
                    // Barang fields
                    'mb.id_barang',
                    'mb.kode_barang',
                    'mb.nama_barang',
                    'mb.kapasitas'
                );

            // Filter berdasarkan tanggal transaksi
            if ($request->filled('tanggal_dari') && $request->filled('tanggal_sampai')) {
                $query->whereBetween('t.tanggal_transaksi', [$request->tanggal_dari, $request->tanggal_sampai]);
            }

            // Filter berdasarkan ID customer
            if ($request->filled('id_customer')) {
                $query->where('bk.id_customer', $request->id_customer);
            }

            // Filter berdasarkan tipe transaksi
            if ($request->has('tipe_transaksi')) {
                $query->where('t.jenis_transaksi', $request->input('tipe_transaksi'));
            }

            // Search keyword
            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->where('t.no_transaksi', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('t.nama_pengirim', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('c.nama_customer', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('mb.nama_barang', 'LIKE', '%' . $keyword . '%');
                });
            }

            $results = $query->orderBy('t.id_transaksi', 'desc')->get();

            // Transform data menjadi struktur nested untuk frontend
            $transaksis = $results->map(function ($item) {
                return [
                    'transaksi' => [
                        'id_transaksi' => $item->id_transaksi,
                        'no_transaksi' => $item->no_transaksi,
                        'tanggal_transaksi' => $item->tanggal_transaksi,
                        'jenis_transaksi' => $item->jenis_transaksi,
                        'jumlah_tabung_isi' => $item->jumlah_tabung_isi,
                        'jumlah_tabung_kosong' => $item->jumlah_tabung_kosong,
                        'jumlah_pinjam_tabung' => $item->jumlah_pinjam_tabung,
                        'total_harga' => $item->total_harga,
                        'metode_pembayaran' => $item->metode_pembayaran,
                        'alamat_pengiriman' => $item->alamat_pengiriman,
                        'nama_pengirim' => $item->nama_pengirim,
                        'keterangan' => $item->keterangan,
                        'harga_satuan' => $item->harga_satuan,
                        'customer' => [
                            'id_customer' => $item->id_customer,
                            'nama_customer' => $item->nama_customer,
                            'alamat' => $item->alamat,
                            'telepon' => $item->telepon,
                            'email' => $item->email
                        ],
                        'barang' => [
                            'id_barang' => $item->id_barang,
                            'kode_barang' => $item->kode_barang,
                            'nama_barang' => $item->nama_barang,
                            'kapasitas' => $item->kapasitas
                        ],

                    ],
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Data transaksi berhasil diambil',
                'data' => $transaksis,
                'total' => $transaksis->count(),
                'search_params' => [
                    'tanggal_dari' => $request->input('tanggal_dari'),
                    'tanggal_sampai' => $request->input('tanggal_sampai'),
                    'id_customer' => $request->input('id_customer'),
                    'tipe_transaksi' => $request->input('tipe_transaksi'),
                    'keyword' => $request->input('keyword'),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating laporan transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
