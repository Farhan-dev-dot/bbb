<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksipenerimaanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_penerimaan' => $this->id_penerimaan,
            'id_barang' => $this->id_barang,
            'tanggal_penerimaan' => $this->tanggal_penerimaan,
            'tabung_isi' => $this->tabung_isi,
            'tabung_kosong' => $this->tabung_kosong,
            "pinjam_tabung" => $this->pinjam_tabung,
            "barang" => new MasterbarangResource($this->whenLoaded('barang')),
        ];
    }
}
