<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangKeluarModel extends Model
{
    use HasFactory;

    protected $table = 'dbo_barang_keluar';
    protected $primaryKey = 'id_keluar';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_transaksi',
        'id_barang',
        'id_customer',
        'nama_pengirim',
        'jumlah_isi',
        'jumlah_kosong',
        'pinjam_tabung',
        'harga_satuan',
        'total_harga',
        'status',
        'keterangan',
        'tanggal_keluar',
        'created_at'
    ];

    protected $casts = [
        'tanggal_keluar' => 'datetime',
        'created_at' => 'timestamp'
    ];

    /**
     * Relationship dengan MasterBarangModel
     */
    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }

    /**
     * Relationship dengan CustomerModel
     */
    public function customer()
    {
        return $this->belongsTo(MasterCustomerModel::class, 'id_customer', 'id_customer');
    }

    public function transaksipengiriman()
    {
        return $this->belongsTo(DboTransaksiModel::class, 'id_transaksi', 'id_transaksi');
    }

    public function riwayatstok()
    {
        return $this->hasMany(RiwayatStokModel::class, 'id_barang', 'id_barang');
    }
}
