<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DboTransaksiModel extends Model
{
    protected $table = 'dbo_transaksi';
    protected $primaryKey = 'id_transaksi';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'no_transaksi',
        'tanggal_transaksi',
        'jenis_transaksi',
        'jumlah_tabung_isi',
        'jumlah_tabung_kosong',
        'jumlah_pinjam_tabung',
        'total_harga',
        'metode_pembayaran',
        'alamat_pengiriman',
        'nama_pengirim',
        'keterangan'
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'jumlah_tabung_isi' => 'integer',
        'jumlah_tabung_kosong' => 'integer',
        'jumlah_pinjam_tabung' => 'integer',
        'total_harga' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = [
        'tanggal_transaksi',
        'created_at',
        'updated_at'
    ];

    /**
     * Relationship dengan MasterBarangModel
     */
    public function barang()
    {
        return $this->belongsTo(MasterBarangModel::class, 'id_barang', 'id_barang');
    }

    /**
     * Relationship dengan MasterCustomerModel
     */
    public function customer()
    {
        return $this->belongsTo(MasterCustomerModel::class, 'id_customer', 'id_customer');
    }

    /**
     * Relationship dengan BarangKeluarModel (optional)
     */
    public function barangKeluar()
    {
        return $this->belongsTo(BarangKeluarModel::class, 'id_barang_keluar', 'id_keluar');
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('tanggal_transaksi', $date);
    }

    /**
     * Scope untuk filter berdasarkan bulan dan tahun
     */
    public function scopeByMonth($query, $month, $year)
    {
        return $query->whereMonth('tanggal_transaksi', $month)
            ->whereYear('tanggal_transaksi', $year);
    }

    /**
     * Scope untuk filter berdasarkan status transaksi
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status_transaksi', $status);
    }

    /**
     * Scope untuk filter berdasarkan customer
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('id_customer', $customerId);
    }

    /**
     * Scope untuk filter berdasarkan barang
     */
    public function scopeByBarang($query, $barangId)
    {
        return $query->where('id_barang', $barangId);
    }

    /**
     * Scope untuk filter berdasarkan range tanggal
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
    }

    /**
     * Accessor untuk format nomor transaksi
     */
    public function getFormattedNoTransaksiAttribute()
    {
        return $this->no_transaksi;
    }

    /**
     * Accessor untuk status badge
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => ['class' => 'warning', 'text' => 'Menunggu'],
            'proses' => ['class' => 'info', 'text' => 'Proses'],
            'selesai' => ['class' => 'success', 'text' => 'Selesai'],
            'batal' => ['class' => 'danger', 'text' => 'Batal'],
            'retur' => ['class' => 'secondary', 'text' => 'Retur']
        ];

        return $badges[$this->status_transaksi] ?? ['class' => 'light', 'text' => 'Unknown'];
    }



    /**
     * Mutator untuk auto generate nomor transaksi jika kosong
     */
    public function setNoTransaksiAttribute($value)
    {
        if (empty($value)) {
            // Generate nomor transaksi dengan format: TRX-YYYYMMDD-XXXX
            $today = now()->format('Ymd');
            $lastNumber = self::whereDate('created_at', now())
                ->where('no_transaksi', 'like', "TRX-{$today}-%")
                ->count();

            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            $this->attributes['no_transaksi'] = "TRX-{$today}-{$nextNumber}";
        } else {
            $this->attributes['no_transaksi'] = $value;
        }
    }
}
