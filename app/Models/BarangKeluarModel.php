<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangKeluarModel extends Model
{
    protected $table = 'dbo_barang_keluar';
    protected $primaryKey = 'id_keluar';

    protected $fillable = [
        'id_transaksi',
        'id_barang',
        'id_customer',
        'nama_pengirim',
        'tanggal_keluar',
        'jumlah_isi',
        'jumlah_kosong',
        'pinjam_tabung',
        'harga_satuan',
        'total_harga',
        'status',
        'keterangan'
    ];

    protected $casts = [
        'tanggal_keluar' => 'date',
        'harga_satuan' => 'decimal:2',
        'diskon_item' => 'decimal:2',
        'subtotal_item' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relasi ke transaksi
    public function transaksi()
    {
        return $this->belongsTo(TransaksiModel::class, 'id_transaksi', 'id_transaksi');
    }

    // Alias untuk transaksi (untuk compatibility)
    public function transaksipengiriman()
    {
        return $this->belongsTo(DboTransaksiModel::class, 'id_transaksi', 'id_transaksi');
    }

    // Relasi ke master barang
    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerModel::class, 'id_customer', 'id_customer');
    }

    // Relasi ke riwayat stok
    public function riwayatStok()
    {
        return $this->hasMany(RiwayatStokModel::class, 'id_keluar', 'id_keluar');
    }
}
