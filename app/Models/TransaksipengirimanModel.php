<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksipengirimanModel extends Model
{
    protected $table = 'dbo_pengiriman';

    protected $primaryKey = 'id_pengiriman';

    protected $fillable = [
        'id_pengiriman',
        'id_barang',
        'id_customer',
        'tanggal_pengiriman',
        'lokasi_pengiriman',
        'penerima',
        'tabung_isi',
        'tabung_kosong',
        'pinjam_tabung',
        'total_harga',
        'keterangan',
        'created_at',
        'updated_at',
    ];


    protected $casts = [
        'tanggal_pengiriman' => 'date',
        'tabung_isi' => 'integer',
        'tabung_kosong' => 'integer',
        'pinjam_tabung' => 'integer',
        'total_harga' => 'integer',
    ];

    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }

    public function customer()
    {
        return $this->belongsTo(MasterCustomerModel::class, 'id_customer', 'id_customer');
    }
}
