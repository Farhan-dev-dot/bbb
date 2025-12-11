<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiModel extends Model
{
    protected $table = 'dbo_transaksi';
    protected $primaryKey = 'id_transaksi';

    protected $fillable = [
        'no_transaksi',
        'id_customer',
        'tanggal_transaksi',
        'jenis_transaksi',
        'jumlah_tabung_isi',
        'jumlah_tabung_kosong',
        'jumlah_pinjam_tabung',
        'subtotal',
        'diskon',
        'biaya_pengiriman',
        'total_harga',
        'metode_pembayaran',
        'status_pembayaran',
        'jumlah_dibayar',
        'sisa_hutang',
        'status_transaksi',
        'alamat_pengiriman',
        'nama_pengirim',
        'status_pengiriman',
        'keterangan'
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'biaya_pengiriman' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'jumlah_dibayar' => 'decimal:2',
        'sisa_hutang' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relasi ke customer
    public function customer()
    {
        return $this->belongsTo(CustomerModel::class, 'id_customer', 'id_customer');
    }

    // Relasi ke barang keluar
    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluarModel::class, 'id_transaksi', 'id_transaksi');
    }

    // Relasi ke riwayat stok
    public function riwayatStok()
    {
        return $this->hasMany(RiwayatStokModel::class, 'id_transaksi', 'id_transaksi');
    }
}
