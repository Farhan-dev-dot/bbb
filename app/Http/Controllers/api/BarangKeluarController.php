<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluarModel;
use App\Models\DboTransaksiModel;
use App\Models\MasterBarangModel;
use App\Models\RiwayatStokModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BarangKeluarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        try {
            $query = BarangKeluarModel::with(['barang', 'customer', 'transaksipengiriman']);

            // Filter by tanggal_keluar from (tanggal mulai)
            if ($request->filled('tanggal_from')) {
                $query->whereDate('tanggal_keluar', '>=', $request->tanggal_from);
            }

            // Filter by tanggal_keluar to (tanggal akhir)
            if ($request->filled('tanggal_to')) {
                $query->whereDate('tanggal_keluar', '<=', $request->tanggal_to);
            }

            if ($request->filled('pengirim')) {
                $query->where('nama_pengirim', 'LIKE', '%' . $request->pengirim . '%');
            }

            // Global search (optional - jika ingin search di kolom lain)
            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('barang', function ($subQuery) use ($keyword) {
                        $subQuery->where('kode_barang', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('nama_barang', 'LIKE', '%' . $keyword . '%');
                    })
                        ->orWhereHas('customer', function ($subQuery) use ($keyword) {
                            $subQuery->where('nama_customer', 'LIKE', '%' . $keyword . '%');
                        })
                        ->orWhereHas('transaksi', function ($subQuery) use ($keyword) {
                            $subQuery->where('no_transaksi', 'LIKE', '%' . $keyword . '%');
                        });
                });
            }

            // Sort options
            $sortBy = $request->input('sortby', 'id_keluar');
            $sortOrder = $request->input('sortorder', 'desc');

            // Validate sort column to prevent SQL injection
            $allowedSortColumns = [
                'id_keluar',
                'tanggal_keluar',
                'jumlah_isi',
                'jumlah_kosong',
                'created_at',
                'updated_at'
            ];

            if (in_array($sortBy, $allowedSortColumns)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('id_keluar', 'desc');
            }

            // Pagination
            $perPage = $request->input('per_page', 10);
            $currentPage = $request->input('page', 1);

            $barangKeluar = $query->paginate($perPage, ['*'], 'page', $currentPage);

            if ($barangKeluar->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data barang keluar kosong',
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
                'message' => 'Data barang keluar berhasil diambil',
                'data' => $barangKeluar->items(),
                'current_page' => $barangKeluar->currentPage(),
                'per_page' => $barangKeluar->perPage(),
                'total' => $barangKeluar->total(),
                'total_page' => $barangKeluar->lastPage(),
                'has_next_page' => $barangKeluar->hasMorePages(),
                'has_prev_page' => $barangKeluar->currentPage() > 1,
                'from' => $barangKeluar->firstItem(),
                'to' => $barangKeluar->lastItem()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching barang keluar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_customer' => 'required|exists:dbo_customer,id_customer',
            'tanggal_transaksi' => 'required|date',
            'jenis_transaksi' => 'required|in:penjualan,retur,tukar_tabung,koreksi',
            'metode_pembayaran' => 'required',
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:dbo_master_barang,id_barang',
            'items.*.jumlah_isi' => 'required|integer|min:0',
            'items.*.jumlah_kosong' => 'required|integer|min:0',
            'items.*.jumlah_pinjam_tabung' => 'nullable|integer|min:0',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.diskon' => 'nullable|numeric|min:0',
            'items.*.keterangan' => 'nullable|string|max:255',
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
            // Generate nomor transaksi unik dengan locking
            $lastTransaksi = DboTransaksiModel::whereDate('created_at', today())
                ->lockForUpdate()
                ->orderBy('id_transaksi', 'desc')
                ->first();

            $counter = $lastTransaksi
                ? (int) substr($lastTransaksi->no_transaksi, -4) + 1
                : 1;

            $noTransaksi = 'TRX-' . date('Ymd') . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);

            $items = $request->items;
            $barangKeluarData = [];

            // Hitung total keseluruhan dari semua items
            $totalKeseluruhan = 0;
            $totalSubtotal = 0;
            $totalDiskon = 0;
            $totalJumlahIsi = 0;
            $totalJumlahKosong = 0;
            $totalPinjamTabung = 0;
            $hargaSatuanTransaksi = $items[0]['harga_satuan'] ?? 0;


            foreach ($items as $item) {
                $subtotal = $item['jumlah_isi'] * $item['harga_satuan'];
                $diskon = $item['diskon'] ?? 0;
                $totalHarga = $subtotal - $diskon;

                $totalSubtotal += $subtotal;
                $totalDiskon += $diskon;
                $totalKeseluruhan += $totalHarga;
                $totalJumlahIsi += $item['jumlah_isi'];
                $totalJumlahKosong += $item['jumlah_kosong'];
                $totalPinjamTabung += $item['jumlah_pinjam_tabung'];
            }

            // Tambahkan biaya pengiriman ke total
            $biayaPengiriman = $request->biaya_pengiriman ?? 0;
            $grandTotal = $totalKeseluruhan + $biayaPengiriman;

            // 1. BUAT TRANSAKSI HEADER (1 kali saja) - TANPA id_barang
            $transaksiHeader = DboTransaksiModel::create([
                'no_transaksi' => $noTransaksi,
                'id_customer' => $request->id_customer,
                'id_barang' => null,
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'jenis_transaksi' => $request->jenis_transaksi,
                'jumlah_tabung_isi' => $totalJumlahIsi,
                'jumlah_tabung_kosong' => $totalJumlahKosong,
                'jumlah_pinjam_tabung' => $totalPinjamTabung,
                'harga_satuan' => $hargaSatuanTransaksi,
                'subtotal' => $totalSubtotal,
                'diskon' => $totalDiskon,
                'total_harga' => $grandTotal,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_pembayaran' => $request->status_pembayaran,
                'jumlah_dibayar' => $request->status_pembayaran === 'lunas' ? $grandTotal : 0,
                'sisa_hutang' => $request->status_pembayaran === 'lunas' ? 0 : $grandTotal,
                'status_transaksi' => 'pending',
                'alamat_pengiriman' => $request->alamat_pengiriman,
                'biaya_pengiriman' => $biayaPengiriman,
                'nama_pengirim' => $request->nama_pengirim,
                'status_pengiriman' => 'belum_kirim',
                'keterangan' => $request->keterangan,
            ]);

            // 2. PROSES SETIAP ITEM
            foreach ($items as $item) {
                $subtotal = $item['jumlah_isi'] * $item['harga_satuan'];
                $diskon = $item['diskon'] ?? 0;
                $totalHarga = $subtotal - $diskon;

                // Ambil data barang untuk cek stok awal
                $barang = MasterBarangModel::find($item['id_barang']);
                if (!$barang) {
                    throw new \Exception("Barang dengan ID {$item['id_barang']} tidak ditemukan");
                }

                // Simpan stok sebelum perubahan
                $stokIsiSebelum = $barang->stok_tabung_isi;
                $stokKosongSebelum = $barang->stok_tabung_kosong;

                // Buat Barang Keluar untuk setiap item
                $barangKeluar = BarangKeluarModel::create([
                    'id_transaksi' => $transaksiHeader->id_transaksi, // WAJIB ADA!
                    'id_barang' => $item['id_barang'],
                    'id_customer' => $request->id_customer,
                    'nama_pengirim' => $request->nama_pengirim ?? null,
                    'jumlah_isi' => $item['jumlah_isi'],
                    'jumlah_kosong' => $item['jumlah_kosong'],
                    'pinjam_tabung' => $item['jumlah_pinjam_tabung'],
                    'harga_satuan' => $item['harga_satuan'],
                    'total_harga' => $totalHarga,
                    'status' => $request->metode_pembayaran,
                    'tanggal_keluar' => $request->tanggal_transaksi,
                    'keterangan' => $item['keterangan'] ?? null,
                ]);

                $barangKeluarData[] = [
                    'id_keluar' => $barangKeluar->id_keluar,
                    'id_barang' => $item['id_barang'],
                    'jumlah_isi' => $item['jumlah_isi'],
                    'jumlah_kosong' => $item['jumlah_kosong'],
                    'total_harga' => $totalHarga
                ];

                // Update stok barang (kurangi stok)
                $barang->stok_tabung_isi -= $item['jumlah_isi'];
                $barang->stok_tabung_kosong -= $item['jumlah_kosong'];
                $barang->save();

                if ($stokIsiSebelum == null || $stokKosongSebelum == null) {
                    throw new \Exception("Stok awal untuk barang ID {$item['id_barang']} tidak valid");
                }
                RiwayatStokModel::create([
                    'id_barang' => $item['id_barang'],
                    'id_transaksi' => $transaksiHeader->id_transaksi,
                    'tipe_transaksi' => 'KELUAR',
                    'jenis_transaksi' => $request->jenis_transaksi,
                    'perubahan_isi' => -$item['jumlah_isi'],
                    'perubahan_kosong' => -$item['jumlah_kosong'],
                    'stok_awal_isi' => $stokIsiSebelum,
                    'stok_awal_kosong' => $stokKosongSebelum,
                    'stok_isi_setelah' => $barang->stok_tabung_isi,
                    'stok_kosong_setelah' => $barang->stok_tabung_kosong,
                    'tanggal_transaksi' => $request->tanggal_transaksi,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Transaksi berhasil dibuat dengan ' . count($items) . ' item(s)',
                'data' => [
                    'id_transaksi' => $transaksiHeader->id_transaksi,
                    'no_transaksi' => $noTransaksi,
                    'tanggal_transaksi' => $transaksiHeader->tanggal_transaksi,
                    'customer' => [
                        'id_customer' => $request->id_customer,
                    ],
                    'items' => $barangKeluarData,
                    'total_items' => count($items),
                    'subtotal' => $totalSubtotal,
                    'total_diskon' => $totalDiskon,
                    'biaya_pengiriman' => $biayaPengiriman,
                    'grand_total' => $grandTotal,
                    'status_pembayaran' => $request->status_pembayaran,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error creating transaksi',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Ambil data dari BarangKeluarModel berdasarkan id_keluar
            $barangKeluar = BarangKeluarModel::with([
                'barang',        // relasi ke MasterBarangModel
                'customer',      // relasi ke Customer
                'transaksipengiriman'      // relasi ke DboTransaksi (header)
            ])->findOrFail($id);

            // Format response
            $response = [
                'id_keluar' => $barangKeluar->id_keluar,
                'tanggal_keluar' => $barangKeluar->tanggal_keluar,
                'nama_pengirim' => $barangKeluar->nama_pengirim,
                'status' => $barangKeluar->status,
                'jumlah_isi' => $barangKeluar->jumlah_isi,
                'jumlah_kosong' => $barangKeluar->jumlah_kosong,
                'pinjam_tabung' => $barangKeluar->pinjam_tabung,
                'harga_satuan' => $barangKeluar->harga_satuan,
                'total_harga' => $barangKeluar->total_harga,
                'keterangan' => $barangKeluar->keterangan,
                'barang' => [
                    'id_barang' => $barangKeluar->id_barang,
                    'kode_barang' => optional($barangKeluar->barang)->kode_barang,
                    'nama_barang' => optional($barangKeluar->barang)->nama_barang,
                ],

                'customer' => [
                    'id_customer' => $barangKeluar->id_customer,
                    'nama_customer' => optional($barangKeluar->customer)->nama_customer,
                    'alamat' => optional($barangKeluar->customer)->alamat,
                    'telepon' => optional($barangKeluar->customer)->telepon,
                ],
            ];

            return response()->json([
                'status' => true,
                'message' => 'Detail barang keluar berhasil diambil',
                'data' => $response
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Barang keluar tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching data',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'id_barang' => 'sometimes|exists:dbo_master_barang,id_barang',
            'jumlah_isi' => 'sometimes|integer|min:0',
            'jumlah_kosong' => 'sometimes|integer|min:0',
            'pinjam_tabung' => 'sometimes|integer|min:0',
            'harga_satuan' => 'sometimes|numeric|min:0',
            'diskon' => 'sometimes|numeric|min:0',
            'keterangan' => 'nullable|string|max:255',
            'tanggal_transaksi' => 'sometimes|date',
            'nama_pengirim' => 'sometimes|string|max:150',
            'metode_pembayaran' => 'sometimes|string|max:50',
            'status_pembayaran' => 'sometimes|string|max:50',
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
            $barangKeluar = BarangKeluarModel::findOrFail($id);
            $validated = $validator->validated();

            // Simpan data lama
            $oldIdBarang = $barangKeluar->id_barang;
            $oldJumlahIsi = $barangKeluar->jumlah_isi;
            $oldJumlahKosong = $barangKeluar->jumlah_kosong;

            // 1. ROLLBACK STOK BARANG LAMA
            $oldBarang = MasterBarangModel::find($oldIdBarang);
            $oldBarang->stok_tabung_isi += $oldJumlahIsi;
            $oldBarang->stok_tabung_kosong += $oldJumlahKosong;
            $oldBarang->save();

            // 2. SET DATA BARU (fallback jika tidak dikirim)
            $newIdBarang = $validated['id_barang'] ?? $oldIdBarang;
            $newJumlahIsi = $validated['jumlah_isi'] ?? $oldJumlahIsi;
            $newJumlahKosong = $validated['jumlah_kosong'] ?? $oldJumlahKosong;
            $newPinjam = $validated['pinjam_tabung'] ?? $barangKeluar->pinjam_tabung;
            $newHargaSatuan = $validated['harga_satuan'] ?? $barangKeluar->harga_satuan;
            $newDiskon = $validated['diskon'] ?? 0;
            $newKeterangan = $validated['keterangan'] ?? $barangKeluar->keterangan;
            $newTanggal = $validated['tanggal_transaksi'] ?? $barangKeluar->tanggal_keluar;
            $newNamaPengirim = $validated['nama_pengirim'] ?? $barangKeluar->nama_pengirim;

            // 3. AMBIL BARANG BARU DAN CEK STOK
            $newBarang = MasterBarangModel::findOrFail($newIdBarang);

            // Validasi stok
            if ($newBarang->stok_tabung_isi < $newJumlahIsi) {
                throw new \Exception("Stok tabung isi tidak cukup!");
            }
            if ($newBarang->stok_tabung_kosong < $newJumlahKosong) {
                throw new \Exception("Stok tabung kosong tidak cukup!");
            }

            // 4. KURANGI STOK BARU
            $newBarang->stok_tabung_isi -= $newJumlahIsi;
            $newBarang->stok_tabung_kosong -= $newJumlahKosong;
            $newBarang->save();


            $stokAkhirIsi = $newBarang->stok_tabung_isi;
            $stokAkhirKosong = $newBarang->stok_tabung_kosong;

            $riwayatStok = RiwayatStokModel::where('id_transaksi', $barangKeluar->id_transaksi)
                ->where('id_barang', $oldIdBarang)
                ->where('tipe_transaksi', 'KELUAR')
                ->first();

            if (!$riwayatStok) {
                throw new \Exception("Riwayat stok tidak ditemukan untuk transaksi ini");
            }

            // Hitung stok awal dengan benar
            $stokAwalIsi = $riwayatStok->stok_awal_isi;
            $stokAwalKosong = $riwayatStok->stok_awal_kosong;



            // 5. UPDATE RIWAYAT STOK
            RiwayatStokModel::where('id_transaksi', $barangKeluar->id_transaksi)
                ->where('id_barang', $oldIdBarang)
                ->where('tipe_transaksi', 'KELUAR')
                ->update([
                    'id_barang' => $newIdBarang,
                    'stok_awal_isi' => $stokAwalIsi,
                    'stok_awal_kosong' => $stokAwalKosong,
                    'stok_isi_setelah' => $stokAkhirIsi,
                    'stok_kosong_setelah' => $stokAkhirKosong,
                    'tanggal_transaksi' => $newTanggal,
                    'perubahan_isi' => -$newJumlahIsi,
                    'perubahan_kosong' => -$newJumlahKosong,
                ]);

            // 6. HITUNG ULANG HARGA
            $subtotal = $newJumlahIsi * $newHargaSatuan;
            $totalHarga = $subtotal - $newDiskon;

            // 7. UPDATE BARANG KELUAR
            $barangKeluar->update([
                'id_barang' => $newIdBarang,
                'jumlah_isi' => $newJumlahIsi,
                'jumlah_kosong' => $newJumlahKosong,
                'pinjam_tabung' => $newPinjam,
                'harga_satuan' => $newHargaSatuan,
                'total_harga' => $totalHarga,
                'keterangan' => $newKeterangan,
                'tanggal_keluar' => $newTanggal,
                'nama_pengirim' => $newNamaPengirim,
            ]);

            // 8. UPDATE HEADER TRANSAKSI (recalculate)
            $header = DboTransaksiModel::find($barangKeluar->id_transaksi);
            $allItems = BarangKeluarModel::where('id_transaksi', $header->id_transaksi)->get();
            $header->jumlah_tabung_isi = $allItems->sum('jumlah_isi');
            $header->jumlah_tabung_kosong = $allItems->sum('jumlah_kosong');
            $header->subtotal = $allItems->sum('total_harga');
            $header->total_harga = $header->subtotal + $header->biaya_pengiriman;
            $header->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Barang keluar berhasil diupdate',
                'data' => $barangKeluar
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error updating',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $barangKeluar = BarangKeluarModel::with(['barang', 'customer'])->findOrFail($id);

            // Rollback stok di master barang
            $masterBarang = MasterBarangModel::where('id_barang', $barangKeluar->id_barang)->first();
            $totalKebutuhan = $barangKeluar->jumlah_isi + $barangKeluar->pinjam_tabung;

            $masterBarang->update([
                'stok_tabung_isi' => $masterBarang->stok_tabung_isi + $totalKebutuhan,
                'stok_tabung_kosong' => $masterBarang->stok_tabung_kosong - $barangKeluar->jumlah_kosong
            ]);

            // Hapus dari dbo_transaksi
            DboTransaksiModel::where('id_barang_keluar', $id)->delete();

            // Hapus riwayat stok
            RiwayatStokModel::where('id_transaksi', $id)
                ->where('tipe_transaksi', 'KELUAR')
                ->delete();

            // Hapus transaksi
            $barangKeluar->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Barang keluar berhasil dihapus',
                'data' => $barangKeluar
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Barang keluar tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error deleting barang keluar: ' . $e->getMessage()
            ], 500);
        }
    }
}
