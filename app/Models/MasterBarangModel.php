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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($barang) {
            $awalanKode = "BRG";
            $tahunSekarang = date('Y');

            $kodeSebelumnya = DB::table('dbo_master_barang')
                ->whereYear('created_at', $tahunSekarang)
                ->orderBy('id_barang', 'desc')
                ->value('kode_barang');

            if ($kodeSebelumnya === null) {
                $nomorUrut = 1;
            } else {
                $nomorUrut = intval(substr($kodeSebelumnya, -4)) + 1;
            }

            $kodeBaru = $awalanKode
                . "-" . $tahunSekarang
                . "-" . str_pad($nomorUrut, 4, "0", STR_PAD_LEFT);

            $barang->kode_barang = $kodeBaru;
        });
    }

    // Relasi ke barang keluar
    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluarModel::class, 'id_barang', 'id_barang');
    }

    // Relasi ke barang masuk
    public function barangMasuk()
    {
        return $this->hasMany(BarangMasukModel::class, 'id_barang', 'id_barang');
    }

    // Relasi ke riwayat stok
    public function riwayatStok()
    {
        return $this->hasMany(RiwayatStokModel::class, 'id_barang', 'id_barang');
    }

    // Relasi ke stok opname
    public function stokOpname()
    {
        return $this->hasMany(StokOpnameModel::class, 'id_barang', 'id_barang');
    }
}
