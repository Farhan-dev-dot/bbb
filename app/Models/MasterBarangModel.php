<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterBarangModel extends Model
{

    protected $table = 'dbo_master_barang';

    protected $primaryKey = 'id_barang';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kapasitas',
        'harga_jual',
        'stok_tabung',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'harga_jual' => 'integer',
    ];
}
