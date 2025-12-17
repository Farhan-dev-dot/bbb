<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterbarangResource extends JsonResource
{
    public static $wrap = 'master_barang';

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id_barang' => $this->id_barang,
            'nama_barang' => $this->nama_barang,
            'kapasitas' => $this->kapasitas,
            'harga_jual' => $this->harga_jual,
            'stok_tabung_isi' => $this->stok_tabung_isi,
            'stok_tabung_kosong' => $this->stok_tabung_kosong,

        ];
    }
}
