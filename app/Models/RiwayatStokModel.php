<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStokModel extends Model
{
    protected $table = 'dbo_riwayat_stok';

    protected $primaryKey = 'id_riwayat';

    protected $fillable = [
        'id_barang',
        'id_transaksi',
        'tipe_transaksi',
        'perubahan_isi',
        'perubahan_kosong',
        'stok_awal_isi',
        'stok_awal_kosong',
        'stok_isi_setelah',
        'stok_kosong_setelah',
        'tanggal_transaksi'
    ];

    protected $casts = [
        'tanggal_transaksi' => 'datetime',
        'perubahan_isi' => 'integer',
        'perubahan_kosong' => 'integer',
        'stok_isi_setelah' => 'integer',
        'stok_kosong_setelah' => 'integer'
    ];

    public $timestamps = false; // karena tidak ada created_at dan updated_at

    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }

    public function transaksi()
    {
        return $this->belongsTo(DboTransaksiModel::class, 'id_transaksi', 'id_transaksi');
    }


    public function barangkeluar()
    {
        return $this->belongsTo(BarangKeluarModel::class, 'id_transaksi', 'id_keluar');
    }

    public function barangmasuk()
    {
        return $this->belongsTo(BarangMasukModel::class, 'id_transaksi', 'id_masuk');
    }

    /**
     * Scope untuk filter berdasarkan tipe transaksi
     */
    public function scopeTipeTransaksi($query, $tipe)
    {
        return $query->where('tipe_transaksi', $tipe);
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeTanggalRange($query, $dari, $sampai)
    {
        return $query->whereBetween('tanggal_transaksi', [$dari, $sampai]);
    }
}
