<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterCustomerModel extends Model
{
    protected $table = 'dbo_customer';

    protected $primaryKey = 'id_customer';

    protected $fillable = [
        'id_customer',
        'kode_customer',
        'nama_customer',
        'alamat',
        'telepon',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'id_customer' => 'integer',
    ];

    public function pengiriman()
    {

        return $this->hasMany(BarangKeluarModel::class, 'id_customer', 'id_customer');
    }
    public function pemasukan()
    {
        return $this->hasMany(BarangMasukModel::class, 'id_customer', 'id_customer');
    }

    public function transaksipengiriman()
    {
        return $this->hasMany(DboTransaksiModel::class, 'id_customer', 'id_customer');
    }
}
