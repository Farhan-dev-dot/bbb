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
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'harga_jual' => $this->harga_jual,
            'stok' => $this->stok,
            'kategori' => $this->whenLoaded('kategori', function () {
                return [
                    'id_kategori' => $this->kategori->id_kategori,
                    'nama_kategori' => $this->kategori->nama_kategori,
                ];
            }),
        ];
    }
}
