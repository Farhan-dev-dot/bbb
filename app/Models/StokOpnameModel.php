<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokOpnameModel extends Model
{
    protected $table = 'dbo_stok_opname';
    protected $primaryKey = 'id_opname';

    protected $fillable = [
        'id_barang',
        'tanggal_opname',
        'stok_isi_sistem',      // Field yang benar sesuai database
        'stok_kosong_sistem',   // Field yang benar sesuai database
        'stok_isi_fisik',       // Field yang benar sesuai database
        'stok_kosong_fisik',    // Field yang benar sesuai database
        'selisih_isi',
        'selisih_kosong',
        'keterangan',
        'created_by'
    ];

    protected $casts = [
        'tanggal_opname' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relasi ke master barang
    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }

    // Relasi ke riwayat stok
    public function riwayatStok()
    {
        return $this->hasMany(RiwayatStokModel::class, 'id_opname', 'id_opname');
    }
}
