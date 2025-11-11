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

    public function pengiriman()
    {
        return $this->hasMany(TransaksipengirimanModel::class, 'id_customer', 'id_customer');
    }
}
