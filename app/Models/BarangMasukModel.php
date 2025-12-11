<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangMasukModel extends Model
{
    protected $table = 'dbo_barang_masuk';
    protected $primaryKey = 'id_masuk';

    protected $guarded = [];

    protected $fillable = [
        'id_barang',
        'id_customer',
        'tanggal_masuk',
        'jumlah_isi',
        'jumlah_kosong',
        'keterangan'
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relasi ke master barang
    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }

    // Relasi ke customer
    public function customer()
    {
        return $this->belongsTo(CustomerModel::class, 'id_customer', 'id_customer');
    }

    // Relasi ke riwayat stok
    public function riwayatStok()
    {
        return $this->hasMany(RiwayatStokModel::class, 'id_masuk', 'id_masuk');
    }
}
