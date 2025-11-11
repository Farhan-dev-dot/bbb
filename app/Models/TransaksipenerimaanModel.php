<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksipenerimaanModel extends Model
{
    protected $table = 'dbo_penerimaan';

    protected $primaryKey = 'id_penerimaan';

    protected $fillable = [
        'id_penerimaan',
        'id_barang',
        'tanggal_penerimaan',
        'tabung_isi',
        'tabung_kosong',
        'pinjam_tabung',
        'created_at',
        'updated_at',
    ];

    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }
}
