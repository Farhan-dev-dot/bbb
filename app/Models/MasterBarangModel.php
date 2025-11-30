<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MasterBarangModel extends Model
{
    protected $table = 'dbo_master_barang';
    protected $primaryKey = 'id_barang';
    public $timestamps = true;

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kapasitas',
        'harga_jual',
        'stok_tabung_isi',
        'stok_tabung_kosong',
        'stok_minimum',
    ];

    protected $casts = [
        'harga_jual' => 'integer',
        'harga_beli' => 'integer',
        'stok_tabung_isi' => 'integer',
        'stok_tabung_kosong' => 'integer',
        'stok_minimum' => 'integer',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($barang) {

            // 1. Bagian awal kode barang
            $awalanKode = "BRG";

            // 2. Tahun saat ini
            $tahunSekarang = date('Y');

            // 3. Ambil kode terakhir berdasarkan kolom 'id_barang'
            $kodeSebelumnya = DB::table('dbo_master_barang')
                ->whereYear('created_at', $tahunSekarang)
                ->orderBy('id_barang', 'desc')
                ->value('kode_barang');

            // 4. Tentukan nomor urut berikutnya
            if ($kodeSebelumnya === null) {

                // Jika belum ada data → mulai dari 1
                $nomorUrut = 1;
            } else {

                // Ambil 4 digit paling belakang lalu +1
                $nomorUrut = intval(substr($kodeSebelumnya, -4)) + 1;
            }

            // 5. Format kode, contoh: BRG-2025-0001
            $kodeBaru = $awalanKode
                . "-" . $tahunSekarang
                . "-" . str_pad($nomorUrut, 4, "0", STR_PAD_LEFT);

            // 6. Simpan kode_barang otomatis
            $barang->kode_barang = $kodeBaru;
        });
    }
}
