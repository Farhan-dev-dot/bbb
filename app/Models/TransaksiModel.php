<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiModel extends Model
{
    protected $table = 'dbo_transaksi';

    protected $primaryKey = 'id_pengiriman';

    protected $fillable = [
        'id_pengiriman',
        'id_barang',
        'id_customer',
        'tanggal_pengiriman',
        'pengirim',
        'pinjam_tabung',
        'alamat',
        'status',
        'jumlah_isi',
        'jumlah_kosong',
        'harga_satuan',
        'total_harga',
        "keterangan",
        'created_at',
        'updated_at',
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
