<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerModel extends Model
{
    protected $table = 'dbo_customer';
    protected $primaryKey = 'id_customer';

    protected $fillable = [
        'nama_customer',
        'alamat',
        'email',
        'telepon'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relasi ke transaksi
    public function transaksi()
    {
        return $this->hasMany(TransaksiModel::class, 'id_customer', 'id_customer');
    }

    // Relasi ke barang keluar
    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluarModel::class, 'id_customer', 'id_customer');
    }
}
