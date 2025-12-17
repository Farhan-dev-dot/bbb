<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MasterCustomerModel;
use App\Models\MasterBarangModel;
use App\Models\RiwayatStokModel;
use App\Models\BarangMasukModel;
use App\Models\BarangKeluarModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get total customer count
     */
    public function totalCustomer()
    {
        try {
            $totalCustomer = MasterCustomerModel::count();

            return response()->json([
                'success' => true,
                'message' => 'Total customer retrieved successfully',
                'data' => [
                    'total_customer' => $totalCustomer
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total barang count
     */
    public function totalBarang()
    {
        try {
            $totalBarang = MasterBarangModel::count();

            return response()->json([
                'success' => true,
                'message' => 'Total barang retrieved successfully',
                'data' => [
                    'total_barang' => $totalBarang
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total stok isi setelah dari riwayat stok terbaru
     */
    public function totalStokAkhirIsi()
    {
        try {
            // Ambil stok_isi_setelah terbaru untuk setiap barang
            $totalStokIsi = DB::table('dbo_riwayat_stok as rs1')
                ->select(DB::raw('SUM(rs1.stok_isi_setelah) as total_stok_isi_setelah'))
                ->whereIn('rs1.id_riwayat', function ($query) {
                    $query->select(DB::raw('MAX(rs2.id_riwayat)'))
                        ->from('dbo_riwayat_stok as rs2')
                        ->groupBy('rs2.id_barang');
                })
                ->first();

            // Fallback ke master barang jika riwayat kosong
            if (!$totalStokIsi || $totalStokIsi->total_stok_isi_setelah == 0) {
                $totalStokIsi = MasterBarangModel::sum('stok_tabung_isi');
                $totalStokIsi = (object) ['total_stok_isi_setelah' => $totalStokIsi];
            }

            return response()->json([
                'success' => true,
                'message' => 'Total stok akhir isi retrieved successfully',
                'data' => [
                    'total_stok_akhir_isi' => (int) ($totalStokIsi->total_stok_isi_setelah ?? 0)
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total stok akhir isi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total stok kosong setelah dari riwayat stok terbaru
     */
    public function totalStokAkhirKosong()
    {
        try {
            // Ambil stok_kosong_setelah terbaru untuk setiap barang
            $totalStokKosong = DB::table('dbo_riwayat_stok as rs1')
                ->select(DB::raw('SUM(rs1.stok_kosong_setelah) as total_stok_kosong_setelah'))
                ->whereIn('rs1.id_riwayat', function ($query) {
                    $query->select(DB::raw('MAX(rs2.id_riwayat)'))
                        ->from('dbo_riwayat_stok as rs2')
                        ->groupBy('rs2.id_barang');
                })
                ->first();

            // Fallback ke master barang jika riwayat kosong
            if (!$totalStokKosong || $totalStokKosong->total_stok_kosong_setelah == 0) {
                $totalStokKosong = MasterBarangModel::sum('stok_tabung_kosong');
                $totalStokKosong = (object) ['total_stok_kosong_setelah' => $totalStokKosong];
            }

            return response()->json([
                'success' => true,
                'message' => 'Total stok akhir kosong retrieved successfully',
                'data' => [
                    'total_stok_akhir_kosong' => (int) ($totalStokKosong->total_stok_kosong_setelah ?? 0)
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total stok akhir kosong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total pendapatan per tanggal dari dbo_barang_keluar
     * Tambahkan parameter ?tanggal=YYYY-MM-DD untuk filter tanggal tertentu
     */
    public function totalPendapatanPerTanggal(Request $request)
    {
        try {
            $query = DB::table('dbo_barang_keluar as bk')
                ->select(
                    DB::raw('DATE(bk.tanggal_keluar) as tanggal'),
                    DB::raw('DAYNAME(DATE(bk.tanggal_keluar)) as nama_hari'),
                    DB::raw('COUNT(DISTINCT bk.id_keluar) as total_transaksi'),
                    DB::raw('COUNT(DISTINCT bk.id_customer) as total_customer'),
                    DB::raw('SUM(bk.total_harga) as total_pendapatan'),
                    DB::raw('AVG(bk.total_harga) as rata_rata_transaksi'),
                    DB::raw('MAX(bk.total_harga) as transaksi_tertinggi'),
                    DB::raw('MIN(bk.total_harga) as transaksi_terendah'),
                    DB::raw('SUM(bk.jumlah_isi) as total_tabung_isi'),
                    DB::raw('SUM(bk.jumlah_kosong) as total_tabung_kosong')
                )
                ->whereNotNull('bk.tanggal_keluar');

            // Filter berdasarkan bulan dan tahun jika ada
            if ($request->has('bulan') && $request->has('tahun')) {
                $query->whereMonth('bk.tanggal_keluar', $request->bulan)
                    ->whereYear('bk.tanggal_keluar', $request->tahun);
            } elseif ($request->has('tanggal') && $request->tanggal) {
                $query->whereDate('bk.tanggal_keluar', $request->tanggal);
            }

            $pendapatanPerTanggal = $query
                ->groupBy(DB::raw('DATE(bk.tanggal_keluar)'), DB::raw('DAYNAME(DATE(bk.tanggal_keluar))'))
                ->orderBy('tanggal', 'desc')
                ->limit(30)
                ->get();

            $formattedData = $pendapatanPerTanggal->map(function ($item) {
                return [
                    'tanggal' => $item->tanggal,
                    'nama_hari' => $item->nama_hari,
                    'tanggal_formatted' => date('d/m/Y', strtotime($item->tanggal)),
                    'total_transaksi' => (int) $item->total_transaksi,
                    'total_customer' => (int) $item->total_customer,
                    'total_pendapatan' => (int) $item->total_pendapatan,
                    'rata_rata_transaksi' => (int) $item->rata_rata_transaksi,
                    'transaksi_tertinggi' => (int) $item->transaksi_tertinggi,
                    'transaksi_terendah' => (int) $item->transaksi_terendah,
                    'total_tabung_isi' => (int) $item->total_tabung_isi,
                    'total_tabung_kosong' => (int) $item->total_tabung_kosong,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Total pendapatan per tanggal retrieved successfully',
                'filter' => [
                    'bulan' => $request->bulan ?? null,
                    'tahun' => $request->tahun ?? null,
                    'tanggal' => $request->tanggal ?? null
                ],
                'data' => $formattedData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total pendapatan per tanggal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distribusi jenis barang berdasarkan transaksi dari dbo_barang_keluar
     * Tambahkan parameter ?tanggal=YYYY-MM-DD untuk filter tanggal tertentu
     */
    public function distribusiJenisBarang(Request $request)
    {
        try {
            // Ambil data dari dbo_barang_keluar yang join dengan dbo_master_barang
            $query = DB::table('dbo_barang_keluar as bk')
                ->join('dbo_master_barang as b', 'bk.id_barang', '=', 'b.id_barang')
                ->select(
                    'b.id_barang',
                    'b.nama_barang',
                    'b.kapasitas',
                    DB::raw('COUNT(*) as jumlah_transaksi'),
                    DB::raw('SUM(bk.total_harga) as total_revenue')
                )
                ->whereNotNull('bk.tanggal_keluar');

            // Filter berdasarkan tanggal jika ada
            if ($request->has('tanggal') && $request->tanggal) {
                $query->whereDate('bk.tanggal_keluar', $request->tanggal);
            }

            $distribusiData = $query
                ->groupBy('b.id_barang', 'b.nama_barang', 'b.kapasitas')
                ->orderByDesc('jumlah_transaksi')
                ->get();

            // Hitung total untuk persentase
            $totalTransaksi = $distribusiData->sum('jumlah_transaksi');
            $totalRevenue = $distribusiData->sum('total_revenue');

            // Format untuk pie chart
            $pieChartData = $distribusiData->map(function ($item) use ($totalTransaksi, $totalRevenue) {
                $persentaseTransaksi = $totalTransaksi > 0 ?
                    round(($item->jumlah_transaksi * 100.0 / $totalTransaksi), 2) : 0;
                $persentaseRevenue = $totalRevenue > 0 ?
                    round(($item->total_revenue * 100.0 / $totalRevenue), 2) : 0;

                // Generate jenis_barang berdasarkan nama_barang atau kapasitas
                $jenisBarang = $this->getJenisBarangFromName($item->nama_barang);

                return [
                    'id_barang' => $item->id_barang,
                    'nama_barang' => $item->nama_barang,
                    'jenis_barang' => $jenisBarang,
                    'kapasitas' => $item->kapasitas,
                    'jumlah_transaksi' => (int) $item->jumlah_transaksi,
                    'total_revenue' => (int) $item->total_revenue,
                    'persentase_transaksi' => $persentaseTransaksi,
                    'persentase_revenue' => $persentaseRevenue,
                    'label' => $item->nama_barang . ' (' . $item->kapasitas . ')',
                    'value' => (int) $item->jumlah_transaksi,
                ];
            });

            // Summary statistik
            $summary = [
                'total_jenis_barang' => $distribusiData->count(),
                'total_transaksi' => $totalTransaksi,
                'total_revenue' => $totalRevenue,
                'jenis_terlaris' => $distribusiData->first(),
                'revenue_tertinggi' => $distribusiData->sortByDesc('total_revenue')->first()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Distribusi jenis barang retrieved successfully',
                'filter' => [
                    'tanggal' => $request->tanggal ?? null
                ],
                'summary' => $summary,
                'data' => $pieChartData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distribusi jenis barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get jenis barang from nama barang
     */
    private function getJenisBarangFromName($namaBarang)
    {
        $namaBarang = strtolower($namaBarang);

        if (strpos($namaBarang, 'lpg') !== false) {
            return 'LPG';
        } elseif (strpos($namaBarang, 'cng') !== false) {
            return 'CNG';
        } elseif (strpos($namaBarang, 'elpiji') !== false) {
            return 'Elpiji';
        } elseif (strpos($namaBarang, 'gas') !== false) {
            return 'LPG'; // Default untuk gas
        }

        return 'Lainnya';
    }

    /**
     * Dashboard Summary - Data ringkasan untuk halaman utama
     * Tambahkan parameter ?tanggal=YYYY-MM-DD untuk filter tanggal tertentu
     */
    public function dashboardSummary(Request $request)
    {
        try {
            // Gunakan tanggal dari request atau default hari ini
            $tanggal = $request->has('tanggal') && $request->tanggal
                ? $request->tanggal
                : now()->format('Y-m-d');

            $summaryHariIni = [
                'transaksi' => [
                    'total' => BarangKeluarModel::whereDate('tanggal_keluar', $tanggal)->count(),
                    'selesai' => BarangKeluarModel::whereDate('tanggal_keluar', $tanggal)
                        ->where('metode_pembayaran', 'completed')->count(),
                    'pending' => BarangKeluarModel::whereDate('tanggal_keluar', $tanggal)
                        ->where('metode_pembayaran', 'pending')->count(),
                    'pendapatan' => BarangKeluarModel::whereDate('tanggal_keluar', $tanggal)
                        ->where('metode_pembayaran', 'completed')->sum('total_harga')
                ],
                'barang_masuk' => BarangMasukModel::whereDate('tanggal_masuk', $tanggal)->count(),
                'barang_keluar' => BarangKeluarModel::whereDate('tanggal_keluar', $tanggal)->count()
            ];

            // Stok summary dari master barang
            $stokSummary = MasterBarangModel::selectRaw('
                SUM(stok_tabung_isi) as total_stok_isi,
                SUM(stok_tabung_kosong) as total_stok_kosong,
                COUNT(*) as total_jenis_barang,
                COUNT(CASE WHEN stok_tabung_isi <= stok_minimum THEN 1 END) as barang_stok_rendah
            ')->first();

            // Transaksi terbaru
            $transaksiTerbaru = BarangKeluarModel::with(['barang', 'customer'])
                ->whereDate('tanggal_keluar', $tanggal)
                ->orderBy('tanggal_keluar', 'desc')
                ->limit(5)
                ->get();

            // Top customer bulan ini (ambil dari barang_keluar)
            $topCustomer = DB::table('dbo_barang_keluar')
                ->join('dbo_customer', 'dbo_barang_keluar.id_customer', '=', 'dbo_customer.id_customer')
                ->whereMonth('dbo_barang_keluar.tanggal_keluar', now()->month)
                ->whereYear('dbo_barang_keluar.tanggal_keluar', now()->year)
                ->select(
                    'dbo_customer.id_customer',
                    'dbo_customer.nama_customer',
                    DB::raw('SUM(dbo_barang_keluar.total_harga) as total_pembelian'),
                    DB::raw('COUNT(DISTINCT dbo_barang_keluar.id_keluar) as total_transaksi')
                )
                ->groupBy('dbo_customer.id_customer', 'dbo_customer.nama_customer')
                ->orderByDesc('total_pembelian')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard summary retrieved successfully',
                'filter' => [
                    'tanggal' => $tanggal
                ],
                'data' => [
                    'summary_hari_ini' => $summaryHariIni,
                    'stok_summary' => $stokSummary,
                    'transaksi_terbaru' => $transaksiTerbaru,
                    'top_customer' => $topCustomer,
                    'last_updated' => now()->toISOString()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total pendapatan per tahun
     * Tambahkan parameter ?tanggal=YYYY-MM-DD untuk filter tanggal tertentu
     */
    public function totalPendapatanPerTahun(Request $request)
    {
        try {
            $query = DB::table('dbo_barang_keluar as bk')
                ->select(
                    DB::raw('YEAR(bk.tanggal_keluar) as tahun'),
                    DB::raw('COUNT(DISTINCT bk.id_keluar) as total_transaksi'),
                    DB::raw('COUNT(DISTINCT bk.id_customer) as total_customer'),
                    DB::raw('SUM(bk.total_harga) as total_pendapatan'),
                    DB::raw('AVG(bk.total_harga) as rata_rata_transaksi'),
                    DB::raw('MAX(bk.total_harga) as transaksi_tertinggi'),
                    DB::raw('MIN(bk.total_harga) as transaksi_terendah')
                )
                ->whereNotNull('bk.tanggal_keluar');

            // Filter berdasarkan tanggal jika ada
            if ($request->has('tanggal') && $request->tanggal) {
                $query->whereDate('bk.tanggal_keluar', $request->tanggal);
            }

            $pendapatanPerTahun = $query
                ->groupBy(DB::raw('YEAR(bk.tanggal_keluar)'))
                ->orderBy('tahun', 'desc')
                ->get();

            $formattedData = $pendapatanPerTahun->map(function ($item) {
                return [
                    'tahun' => $item->tahun,
                    'total_transaksi' => (int) $item->total_transaksi,
                    'total_customer' => (int) $item->total_customer,
                    'total_pendapatan' => (int) $item->total_pendapatan,
                    'rata_rata_transaksi' => (int) $item->rata_rata_transaksi,
                    'transaksi_tertinggi' => (int) $item->transaksi_tertinggi,
                    'transaksi_terendah' => (int) $item->transaksi_terendah,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Total pendapatan per tahun retrieved successfully',
                'filter' => [
                    'tanggal' => $request->tanggal ?? null
                ],
                'data' => $formattedData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total pendapatan per tahun',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total pendapatan hari ini
     * Tambahkan parameter ?tanggal=YYYY-MM-DD untuk filter tanggal tertentu
     */
    public function totalPendapatanHariIni(Request $request)
    {
        try {
            $tanggal = $request->has('tanggal') && $request->tanggal
                ? $request->tanggal
                : now()->format('Y-m-d');

            $totalPendapatan = DB::table('dbo_barang_keluar')
                ->whereDate('tanggal_keluar', $tanggal)
                ->sum('total_harga');

            return response()->json([
                'success' => true,
                'message' => 'Total pendapatan hari ini retrieved successfully',
                'data' => [
                    'tanggal' => $tanggal,
                    'total_pendapatan_hari_ini' => (int) $totalPendapatan
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total pendapatan hari ini',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total transaksi hari ini
     * Tambahkan parameter ?tanggal=YYYY-MM-DD untuk filter tanggal tertentu
     */
    public function totalTransaksiHariIni(Request $request)
    {
        try {
            $tanggal = $request->has('tanggal') && $request->tanggal
                ? $request->tanggal
                : now()->format('Y-m-d');

            $totalTransaksi = DB::table('dbo_barang_keluar')
                ->whereDate('tanggal_keluar', $tanggal)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Total transaksi hari ini retrieved successfully',
                'data' => [
                    'tanggal' => $tanggal,
                    'total_transaksi_hari_ini' => (int) $totalTransaksi
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total transaksi hari ini',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
