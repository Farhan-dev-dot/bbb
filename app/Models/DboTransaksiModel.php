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
        'id_barang',
        'id_customer',
        'tanggal_transaksi',
        'jenis_transaksi',
        'jumlah_tabung_isi',
        'jumlah_tabung_kosong',
        'jumlah_pinjam_tabung',
        'harga_satuan',
        'subtotal',
        'diskon',
        'total_harga',
        'metode_pembayaran',
        'status_pembayaran',
        'jumlah_dibayar',
        'sisa_hutang',
        'status_transaksi',
        'alamat_pengiriman',
        'biaya_pengiriman',
        'nama_pengirim',
        'tanggal_pengiriman',
        'status_pengiriman',
        'keterangan',
        'catatan_internal',
        'id_barang_keluar',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'tanggal_transaksi' => 'datetime',
        'tanggal_pengiriman' => 'datetime',
        'jumlah_tabung_isi' => 'integer',
        'jumlah_tabung_kosong' => 'integer',
        'jumlah_pinjam_tabung' => 'integer',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'jumlah_dibayar' => 'decimal:2',
        'sisa_hutang' => 'decimal:2',
        'biaya_pengiriman' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = [
        'tanggal_transaksi',
        'tanggal_pengiriman',
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
     * Accessor untuk status pembayaran badge
     */
    public function getPaymentBadgeAttribute()
    {
        $badges = [
            'lunas' => ['class' => 'success', 'text' => 'Lunas'],
            'belum_lunas' => ['class' => 'danger', 'text' => 'Belum Lunas'],
            'cicilan' => ['class' => 'warning', 'text' => 'Cicilan']
        ];

        return $badges[$this->status_pembayaran] ?? ['class' => 'light', 'text' => 'Unknown'];
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

    /**
     * Mutator untuk auto hitung subtotal
     */
    public function setSubtotalAttribute($value)
    {
        if ($value === null || $value === 0) {
            $this->attributes['subtotal'] = $this->jumlah_tabung_isi * $this->harga_satuan;
        } else {
            $this->attributes['subtotal'] = $value;
        }
    }

    /**
     * Mutator untuk auto hitung total harga
     */
    public function setTotalHargaAttribute($value)
    {
        if ($value === null || $value === 0) {
            $subtotal = $this->subtotal ?? ($this->jumlah_tabung_isi * $this->harga_satuan);
            $diskon = $this->diskon ?? 0;
            $biaya_pengiriman = $this->biaya_pengiriman ?? 0;
            $this->attributes['total_harga'] = $subtotal - $diskon + $biaya_pengiriman;
        } else {
            $this->attributes['total_harga'] = $value;
        }
    }

    /**
     * Boot method untuk auto calculation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto calculate subtotal jika belum ada
            if (empty($model->subtotal)) {
                $model->subtotal = $model->jumlah_tabung_isi * $model->harga_satuan;
            }

            // Auto calculate total harga jika belum ada
            if (empty($model->total_harga)) {
                $model->total_harga = $model->subtotal - ($model->diskon ?? 0) + ($model->biaya_pengiriman ?? 0);
            }

            // Auto calculate sisa hutang
            $model->sisa_hutang = $model->total_harga - ($model->jumlah_dibayar ?? 0);

            // Auto set status pembayaran
            if ($model->sisa_hutang <= 0) {
                $model->status_pembayaran = 'lunas';
                $model->sisa_hutang = 0;
            } elseif (($model->jumlah_dibayar ?? 0) > 0) {
                $model->status_pembayaran = 'cicilan';
            } else {
                $model->status_pembayaran = 'belum_lunas';
            }
        });

        static::updating(function ($model) {
            // Auto recalculate pada update
            if ($model->isDirty(['jumlah_tabung_isi', 'harga_satuan'])) {
                $model->subtotal = $model->jumlah_tabung_isi * $model->harga_satuan;
            }

            if ($model->isDirty(['subtotal', 'diskon', 'biaya_pengiriman'])) {
                $model->total_harga = $model->subtotal - ($model->diskon ?? 0) + ($model->biaya_pengiriman ?? 0);
            }

            if ($model->isDirty(['total_harga', 'jumlah_dibayar'])) {
                $model->sisa_hutang = $model->total_harga - ($model->jumlah_dibayar ?? 0);

                // Auto update status pembayaran
                if ($model->sisa_hutang <= 0) {
                    $model->status_pembayaran = 'lunas';
                    $model->sisa_hutang = 0;
                } elseif (($model->jumlah_dibayar ?? 0) > 0) {
                    $model->status_pembayaran = 'cicilan';
                } else {
                    $model->status_pembayaran = 'belum_lunas';
                }
            }
        });
    }
}
