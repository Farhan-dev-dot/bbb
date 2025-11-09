<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterBarangModel extends Model
{

    protected $table = 'dbo_barang';

    protected $primaryKey = 'id_barang';

    protected $fillable = [
        'id_barang',
        'id_kategori',
        'kode_barang',
        'nama_barang',
        'kapasitas',
        'harga_jual',
        'stok',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'harga_jual' => 'integer',
        'stok' => 'integer',
    ];
}
