<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStokModel extends Model
{
    protected $table = 'dbo_riwayat_stok';
    protected $primaryKey = 'id_riwayat';

    protected $fillable = [
        'id_barang',
        'tipe_transaksi',
        'perubahan_isi',
        'perubahan_kosong',
        'stok_awal_isi',
        'stok_awal_kosong',
        'stok_isi_setelah',
        'stok_kosong_setelah',
        'tanggal_transaksi',
        'keterangan'
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relasi ke master barang
    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }

    // Note: id_transaksi is just a transaction reference ID
    // No relation to dbo_transaksi table (table has been removed)

    // Relasi ke barang masuk
    public function barangMasuk()
    {
        return $this->belongsTo(BarangMasukModel::class, 'id_masuk', 'id_masuk');
    }

    // Relasi ke stok opname
    public function stokOpname()
    {
        return $this->belongsTo(StokOpnameModel::class, 'id_opname', 'id_opname');
    }
}
