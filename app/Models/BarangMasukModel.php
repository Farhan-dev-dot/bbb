<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangMasukModel extends Model
{
    use HasFactory;

    protected $table = 'dbo_barang_masuk';
    protected $primaryKey = 'id_masuk';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_barang',
        'id_customer',
        'jumlah_isi',
        'jumlah_kosong',
        'keterangan',
        'tanggal_masuk',
        'created_at'
    ];

    protected $casts = [
        'tanggal_masuk' => 'datetime',
        'created_at' => 'timestamp'
    ];

    /**
     * Relationship dengan MasterBarangModel
     */
    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }

    public function customer()
    {
        return $this->belongsTo(MasterCustomerModel::class, 'id_customer', 'id_customer');
    }

    public function riwayatstok()
    {
        return $this->hasMany(RiwayatStokModel::class, 'id_barang', 'id_barang');
    }
}
