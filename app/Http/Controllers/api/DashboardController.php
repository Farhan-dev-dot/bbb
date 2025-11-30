<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\MasterCustomerModel;
use App\Models\MasterBarangModel;
use App\Models\RiwayatStokModel;
use App\Models\BarangMasukModel;
use App\Models\BarangKeluarModel;
use App\Models\DboTransaksiModel;
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
     * Get total pendapatan per tanggal dari dbo_transaksi
     * Tambahkan parameter ?tanggal=YYYY-MM-DD untuk filter tanggal tertentu
     */
    public function totalPendapatanPerTanggal(Request $request)
    {
        try {
            $query = DB::table('dbo_transaksi')
                ->select(
                    DB::raw('DATE(tanggal_transaksi) as tanggal'),
                    DB::raw('DAYNAME(DATE(tanggal_transaksi)) as nama_hari'),
                    DB::raw('COUNT(*) as total_transaksi'),
                    DB::raw('COUNT(DISTINCT id_customer) as total_customer'),
                    DB::raw('SUM(total_harga) as total_pendapatan'),
                    DB::raw('AVG(total_harga) as rata_rata_transaksi'),
                    DB::raw('MAX(total_harga) as transaksi_tertinggi'),
                    DB::raw('MIN(total_harga) as transaksi_terendah'),
                    DB::raw('COUNT(CASE WHEN metode_pembayaran = "transfer" THEN 1 END) as transaksi_transfer'),
                    DB::raw('COUNT(CASE WHEN metode_pembayaran = "tunai" THEN 1 END) as transaksi_tunai'),
                    DB::raw('COUNT(CASE WHEN status_transaksi = "selesai" THEN 1 END) as transaksi_selesai'),
                    DB::raw('COUNT(CASE WHEN status_transaksi = "pending" THEN 1 END) as transaksi_pending')
                )
                ->whereNotNull('tanggal_transaksi');

            // Filter berdasarkan bulan dan tahun jika ada
            if ($request->has('bulan') && $request->has('tahun')) {
                $query->whereMonth('tanggal_transaksi', $request->bulan)
                    ->whereYear('tanggal_transaksi', $request->tahun);
            } elseif ($request->has('tanggal') && $request->tanggal) {
                $query->whereDate('tanggal_transaksi', $request->tanggal);
            }

            $pendapatanPerTanggal = $query
                ->groupBy(DB::raw('DATE(tanggal_transaksi)'), DB::raw('DAYNAME(DATE(tanggal_transaksi))'))
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
                    'transaksi_transfer' => (int) $item->transaksi_transfer,
                    'transaksi_tunai' => (int) $item->transaksi_tunai,
                    'transaksi_selesai' => (int) $item->transaksi_selesai,
                    'transaksi_pending' => (int) $item->transaksi_pending,
                    'tingkat_penyelesaian' => $item->total_transaksi > 0 ?
                        round($item->transaksi_selesai / $item->total_transaksi * 100, 2) : 0
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
     * Get distribusi jenis barang berdasarkan transaksi dari dbo_transaksi
     * Tambahkan parameter ?tanggal=YYYY-MM-DD untuk filter tanggal tertentu
     */
    public function distribusiJenisBarang(Request $request)
    {
        try {
            // Ambil data dari dbo_transaksi yang join dengan dbo_master_barang
            $query = DB::table('dbo_transaksi as t')
                ->join('dbo_master_barang as b', 't.id_barang', '=', 'b.id_barang')
                ->select(
                    'b.id_barang',
                    'b.nama_barang',
                    'b.kapasitas',
                    DB::raw('COUNT(*) as jumlah_transaksi'),
                    DB::raw('SUM(t.total_harga) as total_revenue')
                )
                ->whereNotNull('t.tanggal_transaksi');

            // Filter berdasarkan tanggal jika ada
            if ($request->has('tanggal') && $request->tanggal) {
                $query->whereDate('t.tanggal_transaksi', $request->tanggal);
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
                    'total' => DboTransaksiModel::whereDate('tanggal_transaksi', $tanggal)->count(),
                    'selesai' => DboTransaksiModel::whereDate('tanggal_transaksi', $tanggal)
                        ->where('status_transaksi', 'selesai')->count(),
                    'pending' => DboTransaksiModel::whereDate('tanggal_transaksi', $tanggal)
                        ->where('status_transaksi', 'pending')->count(),
                    'pendapatan' => DboTransaksiModel::whereDate('tanggal_transaksi', $tanggal)
                        ->where('status_transaksi', 'selesai')->sum('total_harga')
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
            $transaksiTerbaru = DboTransaksiModel::with(['barang', 'customer'])
                ->whereDate('tanggal_transaksi', $tanggal)
                ->orderBy('tanggal_transaksi', 'desc')
                ->limit(5)
                ->get();

            // Top customer bulan ini
            $topCustomer = DboTransaksiModel::with('customer')
                ->whereMonth('tanggal_transaksi', now()->month)
                ->whereYear('tanggal_transaksi', now()->year)
                ->where('status_transaksi', 'selesai')
                ->select('id_customer', DB::raw('SUM(total_harga) as total_pembelian'), DB::raw('COUNT(*) as total_transaksi'))
                ->groupBy('id_customer')
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
            $query = DB::table('dbo_transaksi')
                ->select(
                    DB::raw('YEAR(tanggal_transaksi) as tahun'),
                    DB::raw('COUNT(*) as total_transaksi'),
                    DB::raw('COUNT(DISTINCT id_customer) as total_customer'),
                    DB::raw('SUM(total_harga) as total_pendapatan'),
                    DB::raw('AVG(total_harga) as rata_rata_transaksi'),
                    DB::raw('MAX(total_harga) as transaksi_tertinggi'),
                    DB::raw('MIN(total_harga) as transaksi_terendah')
                )
                ->whereNotNull('tanggal_transaksi');

            // Filter berdasarkan tanggal jika ada
            if ($request->has('tanggal') && $request->tanggal) {
                $query->whereDate('tanggal_transaksi', $request->tanggal);
            }

            $pendapatanPerTahun = $query
                ->groupBy(DB::raw('YEAR(tanggal_transaksi)'))
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

            $totalPendapatan = DB::table('dbo_transaksi')
                ->whereDate('tanggal_transaksi', $tanggal)
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

            $totalTransaksi = DB::table('dbo_transaksi')
                ->whereDate('tanggal_transaksi', $tanggal)
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
