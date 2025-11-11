<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_pengiriman' => $this->id_pengiriman,
            'id_barang' => $this->id_barang,
            'id_customer' => $this->id_customer,
            'tanggal_pengiriman' => $this->tanggal_pengiriman,
            'pengirim' => $this->pengirim,
            'alamat' => $this->alamat,
            'status' => $this->status,
            'jumlah_isi' => $this->jumlah_isi,
            'jumlah_kosong' => $this->jumlah_kosong,
            'pinjam_tabung' => $this->pinjam_tabung,
            'harga_satuan' => $this->harga_satuan,
            'total_harga' => $this->total_harga,
            'keterangan' => $this->keterangan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'barang' => $this->whenLoaded('barang'),
            'customer' => $this->whenLoaded('customer'),
        ];
    }
}
