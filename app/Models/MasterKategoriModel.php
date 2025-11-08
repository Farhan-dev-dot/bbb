<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterKategoriModel extends Model
{
    protected $table = 'dbo_kategori';

    protected $primaryKey = 'id_kategori';

    protected $fillable = [
        'id_kategori',
        'nama_kategori',
        'created_at',
        'updated_at',
    ];

    public function barang()
    {
        return $this->hasMany(MasterBarangModel::class, 'id_kategori', 'id_kategori');
    }
}
