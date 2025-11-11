<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MastercustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_customer' => $this->id_customer,
            'kode_customer' => $this->kode_customer,
            'nama_customer' => $this->nama_customer,
            'alamat' => $this->alamat,
            'telepon' => $this->telepon,
        ];
    }
}
