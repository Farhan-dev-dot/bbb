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
