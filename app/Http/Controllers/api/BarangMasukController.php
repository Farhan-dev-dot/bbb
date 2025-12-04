<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BarangMasukModel;
use App\Models\MasterBarangModel;
use App\Models\MasterCustomerModel;
use App\Models\RiwayatStokModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class BarangMasukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        try {
            $query = BarangMasukModel::with(['barang', 'customer']);

            // Filter by tanggal_masuk from (tanggal mulai)
            if ($request->filled('tanggal_from')) {
                $query->whereDate('tanggal_masuk', '>=', $request->tanggal_from);
            }

            // Filter by tanggal_masuk to (tanggal akhir)
            if ($request->filled('tanggal_to')) {
                $query->whereDate('tanggal_masuk', '<=', $request->tanggal_to);
            }


            $sortBy = $request->input('sortby', 'id_masuk');
            $sortOrder = $request->input('sortorder', 'desc');


            $allowedSortColumns = [
                'id_masuk',
                'id_barang',
                'id_customer',
                'tanggal_masuk',
                'jumlah_isi',
                'jumlah_kosong',
                'keterangan',
                'created_at',
                'updated_at'
            ];


            if (in_array($sortBy, $allowedSortColumns)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('id_masuk', 'desc');
            }

            $perPage = $request->input('per_page', 10);
            $currentPage = $request->input('page', 1);


            $barangMasuk = $query->paginate($perPage, ['*'], 'page', $currentPage);

            if ($barangMasuk->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data barang masuk kosong',
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
                'data' => $barangMasuk->items(),
                'current_page' => $barangMasuk->currentPage(),
                'per_page' => $barangMasuk->perPage(),
                'total' => $barangMasuk->total(),
                'total_page' => $barangMasuk->lastPage(),
                'has_next_page' => $barangMasuk->hasMorePages(),
                'has_prev_page' => $barangMasuk->currentPage() > 1,
                'from' => $barangMasuk->firstItem(),
                'to' => $barangMasuk->lastItem()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error building query: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'data' => 'required|array|min:1',
            'data.*.id_barang' => 'required|exists:dbo_master_barang,id_barang',
            'data.*.nama_customer' => 'required|string|max:255',
            'data.*.alamat' => 'required|string|max:500',
            'data.*.email' => 'required|email|max:255',
            'data.*.no_telfon' => 'required|string|max:20',
            'data.*.jumlah_isi' => 'required|integer|min:0',
            'data.*.jumlah_kosong' => 'required|integer|min:0',
            'data.*.keterangan' => 'nullable|string|max:255',
            'data.*.tanggal_masuk' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $result = [];

            foreach ($request->data as $item) {
                // 1. SELALU BUAT CUSTOMER BARU
                $newCustomer = MasterCustomerModel::create([
                    'nama_customer' => $item['nama_customer'],
                    'alamat' => $item['alamat'],
                    'email' => $item['email'],
                    'telepon' => $item['no_telfon'],
                ]);
                $customerId = $newCustomer->id_customer;

                // 2. Insert barang masuk
                $barangMasuk = BarangMasukModel::create([
                    'id_barang' => $item['id_barang'],
                    'id_customer' => $customerId,
                    'jumlah_isi' => $item['jumlah_isi'],
                    'jumlah_kosong' => $item['jumlah_kosong'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'tanggal_masuk' => $item['tanggal_masuk']
                ]);

                // 3. Update stok master barang
                $barang = MasterBarangModel::findOrFail($item['id_barang']);

                // Simpan stok awal sebelum penambahan
                $stokAwalIsi = $barang->stok_tabung_isi;
                $stokAwalKosong = $barang->stok_tabung_kosong;

                $barang->stok_tabung_isi += $item['jumlah_isi'];
                $barang->stok_tabung_kosong += $item['jumlah_kosong'];
                $barang->save();

                // 4. Insert riwayat stok
                RiwayatStokModel::create([
                    'id_barang' => $barang->id_barang,
                    'id_transaksi' => $barangMasuk->id_masuk,
                    'stok_awal_isi' => $stokAwalIsi,
                    'stok_awal_kosong' => $stokAwalKosong,
                    'tipe_transaksi' => 'MASUK',
                    'perubahan_isi' => $item['jumlah_isi'],
                    'perubahan_kosong' => $item['jumlah_kosong'],
                    'stok_isi_setelah' => $barang->stok_tabung_isi,
                    'stok_kosong_setelah' => $barang->stok_tabung_kosong,
                    'tanggal_transaksi' => $item['tanggal_masuk'],
                ]);

                // 5. Load customer data untuk response
                $barangMasuk->load(['barang', 'customer']);

                $result[] = [
                    'barang_masuk' => $barangMasuk,
                    'customer_id' => $customerId,
                    'customer_name' => $item['nama_customer'],
                ];
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Barang masuk batch berhasil ditambahkan dengan ' . count($result) . ' item(s)',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
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
            $barangMasuk = BarangMasukModel::with(['barang', 'customer'])->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Detail barang masuk berhasil diambil',
                'data' => $barangMasuk
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Barang masuk tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching barang masuk detail',
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
            'id_customer' => 'nullable|exists:dbo_customer,id_customer',
            'nama_customer' => 'sometimes|string|max:255',
            'alamat' => 'sometimes|string|max:500',
            'email' => 'sometimes|email|max:255',
            'no_telfon' => 'sometimes|string|max:20',
            'jumlah_isi' => 'sometimes|integer|min:0',
            'jumlah_kosong' => 'sometimes|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
            'tanggal_masuk' => 'sometimes|date'
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
            $barangMasuk = BarangMasukModel::findOrFail($id);
            $validated = $validator->validated();

            // 1. HANDLE CUSTOMER UPDATE
            $customerId = $barangMasuk->id_customer;

            if (isset($validated['id_customer'])) {
                // Jika ada id_customer baru, gunakan yang baru
                $customerId = $validated['id_customer'];
            } elseif (
                isset($validated['nama_customer']) || isset($validated['alamat']) ||
                isset($validated['email']) || isset($validated['no_telfon'])
            ) {
                // Jika ada update data customer, buat customer baru
                $customerData = [];
                if (isset($validated['nama_customer'])) $customerData['nama_customer'] = $validated['nama_customer'];
                if (isset($validated['alamat'])) $customerData['alamat'] = $validated['alamat'];
                if (isset($validated['email'])) $customerData['email'] = $validated['email'];
                if (isset($validated['no_telfon'])) $customerData['no_telfon'] = $validated['no_telfon'];

                if (!empty($customerData)) {
                    // Ambil data customer lama untuk fallback
                    $oldCustomer = MasterCustomerModel::find($customerId);

                    $newCustomer = MasterCustomerModel::create([
                        'nama_customer' => $customerData['nama_customer'] ?? $oldCustomer->nama_customer,
                        'alamat' => $customerData['alamat'] ?? $oldCustomer->alamat,
                        'email' => $customerData['email'] ?? $oldCustomer->email,
                        'telepon' => $customerData['no_telfon'] ?? $oldCustomer->telepon,
                    ]);
                    $customerId = $newCustomer->id_customer;
                }
            }

            // 2. ROLLBACK STOK LAMA
            $oldIdBarang = $barangMasuk->id_barang;
            $oldJumlahIsi = $barangMasuk->jumlah_isi;
            $oldJumlahKosong = $barangMasuk->jumlah_kosong;

            $oldMasterBarang = MasterBarangModel::findOrFail($oldIdBarang);
            $oldMasterBarang->stok_tabung_isi -= $oldJumlahIsi;
            $oldMasterBarang->stok_tabung_kosong -= $oldJumlahKosong;
            $oldMasterBarang->save();

            // 3. APPLY DATA BARU
            $newIdBarang = $validated['id_barang'] ?? $oldIdBarang;
            $newJumlahIsi = $validated['jumlah_isi'] ?? $oldJumlahIsi;
            $newJumlahKosong = $validated['jumlah_kosong'] ?? $oldJumlahKosong;
            $newTanggalMasuk = $validated['tanggal_masuk'] ?? $barangMasuk->tanggal_masuk;
            $newKeterangan = $validated['keterangan'] ?? $barangMasuk->keterangan;

            $newMasterBarang = MasterBarangModel::findOrFail($newIdBarang);

            // Simpan stok sebelum penambahan untuk riwayat
            $stokAwalIsi = $newMasterBarang->stok_tabung_isi;
            $stokAwalKosong = $newMasterBarang->stok_tabung_kosong;

            // Update stok dengan data baru
            $newMasterBarang->stok_tabung_isi += $newJumlahIsi;
            $newMasterBarang->stok_tabung_kosong += $newJumlahKosong;
            $newMasterBarang->save();

            // 4. UPDATE BARANG MASUK
            $barangMasuk->update([
                'id_customer' => $customerId,
                'id_barang' => $newIdBarang,
                'jumlah_isi' => $newJumlahIsi,
                'jumlah_kosong' => $newJumlahKosong,
                'tanggal_masuk' => $newTanggalMasuk,
                'keterangan' => $newKeterangan,
            ]);

            // 5. UPDATE RIWAYAT STOK
            $riwayatStok = RiwayatStokModel::where('id_transaksi', $id)
                ->where('tipe_transaksi', 'MASUK')
                ->first();

            if ($riwayatStok) {
                $riwayatStok->update([
                    'id_barang' => $newIdBarang,
                    'stok_awal_isi' => $stokAwalIsi,
                    'stok_awal_kosong' => $stokAwalKosong,
                    'perubahan_isi' => $newJumlahIsi,
                    'perubahan_kosong' => $newJumlahKosong,
                    'stok_isi_setelah' => $newMasterBarang->stok_tabung_isi,
                    'stok_kosong_setelah' => $newMasterBarang->stok_tabung_kosong,
                    'tanggal_transaksi' => $newTanggalMasuk
                ]);
            }

            DB::commit();

            // Load relations untuk response
            $barangMasuk->load(['barang', 'customer']);

            return response()->json([
                'status' => true,
                'message' => 'Barang masuk berhasil diupdate',
                'data' => [
                    'id_masuk' => $barangMasuk->id_masuk,
                    'id_barang' => $barangMasuk->id_barang,
                    'id_customer' => $barangMasuk->id_customer,
                    'jumlah_isi' => $barangMasuk->jumlah_isi,
                    'jumlah_kosong' => $barangMasuk->jumlah_kosong,
                    'tanggal_masuk' => $barangMasuk->tanggal_masuk,
                    'keterangan' => $barangMasuk->keterangan,
                    'barang' => [
                        'id_barang' => $barangMasuk->id_barang,
                        'kode_barang' => optional($barangMasuk->barang)->kode_barang,
                        'nama_barang' => optional($barangMasuk->barang)->nama_barang,
                        'stok_isi_setelah_update' => $newMasterBarang->stok_tabung_isi,
                        'stok_kosong_setelah_update' => $newMasterBarang->stok_tabung_kosong,
                    ],
                    'customer' => [
                        'id_customer' => $barangMasuk->id_customer,
                        'nama_customer' => optional($barangMasuk->customer)->nama_customer,
                        'alamat' => optional($barangMasuk->customer)->alamat,
                        'email' => optional($barangMasuk->customer)->email,
                        'no_telfon' => optional($barangMasuk->customer)->no_telfon,
                    ],
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Barang masuk tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error updating barang masuk: ' . $e->getMessage(),
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
            $barangMasuk = BarangMasukModel::with(['barang', 'customer'])->findOrFail($id);

            // Simpan data untuk rollback dan response
            $idBarang = $barangMasuk->id_barang;
            $jumlahIsi = $barangMasuk->jumlah_isi;
            $jumlahKosong = $barangMasuk->jumlah_kosong;

            // 1. ROLLBACK STOK di master barang
            $masterBarang = MasterBarangModel::where('id_barang', $idBarang)->first();

            if ($masterBarang) {
                // Simpan stok sebelum rollback untuk informasi
                $stokSebelumRollback = [
                    'stok_isi' => $masterBarang->stok_tabung_isi,
                    'stok_kosong' => $masterBarang->stok_tabung_kosong
                ];

                $masterBarang->update([
                    'stok_tabung_isi' => $masterBarang->stok_tabung_isi - $jumlahIsi,
                    'stok_tabung_kosong' => $masterBarang->stok_tabung_kosong - $jumlahKosong
                ]);

                // Validasi stok tidak negatif
                if ($masterBarang->stok_tabung_isi < 0) {
                    throw new \Exception("Rollback stok isi akan menghasilkan nilai negatif ({$masterBarang->stok_tabung_isi})");
                }
                if ($masterBarang->stok_tabung_kosong < 0) {
                    throw new \Exception("Rollback stok kosong akan menghasilkan nilai negatif ({$masterBarang->stok_tabung_kosong})");
                }
            }

            // 2. HAPUS RIWAYAT STOK yang terkait
            $deletedRiwayat = RiwayatStokModel::where('id_transaksi', $id)
                ->where('tipe_transaksi', 'MASUK')
                ->where('id_barang', $idBarang)
                ->delete();

            // 3. HAPUS BARANG MASUK
            $deletedData = [
                'id_masuk' => $barangMasuk->id_masuk,
                'id_barang' => $idBarang,
                'id_customer' => $barangMasuk->id_customer,
                'jumlah_isi' => $jumlahIsi,
                'jumlah_kosong' => $jumlahKosong,
                'tanggal_masuk' => $barangMasuk->tanggal_masuk,
                'keterangan' => $barangMasuk->keterangan,
                'barang' => [
                    'kode_barang' => optional($barangMasuk->barang)->kode_barang,
                    'nama_barang' => optional($barangMasuk->barang)->nama_barang,
                ],
                'customer' => [
                    'nama_customer' => optional($barangMasuk->customer)->nama_customer,
                    'alamat' => optional($barangMasuk->customer)->alamat,
                ]
            ];

            $barangMasuk->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Barang masuk berhasil dihapus dan stok telah di-rollback',
                'data' => [
                    'deleted_item' => $deletedData,
                    'rollback_info' => [
                        'jumlah_isi_dikurangi' => $jumlahIsi,
                        'jumlah_kosong_dikurangi' => $jumlahKosong,
                        'stok_sebelum_rollback' => $stokSebelumRollback ?? null,
                        'stok_setelah_rollback' => [
                            'stok_isi' => $masterBarang ? $masterBarang->stok_tabung_isi : 0,
                            'stok_kosong' => $masterBarang ? $masterBarang->stok_tabung_kosong : 0,
                        ]
                    ],
                    'riwayat_stok_dihapus' => $deletedRiwayat > 0 ? 'Ya' : 'Tidak ada'
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Barang masuk tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error deleting barang masuk: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
