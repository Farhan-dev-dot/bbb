<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksipengirimanResource extends JsonResource
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
            'lokasi_pengiriman' => $this->lokasi_pengiriman,
            'penerima' => $this->penerima,
            'tabung_isi' => $this->tabung_isi,
            'tabung_kosong' => $this->tabung_kosong,
            'pinjam_tabung' => $this->pinjam_tabung,
            'total_harga' => $this->total_harga,
            'keterangan' => $this->keterangan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships (akan dimuat jika ada)
            'barang' => $this->whenLoaded('barang'),
            'customer' => $this->whenLoaded('customer'),
        ];
    }
}
