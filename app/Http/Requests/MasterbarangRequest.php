<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterbarangRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $barangId = null;

        // Ambil ID dari URL segments
        $segments = $this->segments();
        $masterBarangIndex = array_search('master-barang', $segments);
        if ($masterBarangIndex !== false && isset($segments[$masterBarangIndex + 1])) {
            $barangId = $segments[$masterBarangIndex + 1];
        }

        // Rules untuk update (semua field optional kecuali yang diubah)
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return [
                'nama_barang'         => 'sometimes|string|max:150',
                'kapasitas'        => 'sometimes|string|max:50',
                'harga_jual'       => 'sometimes|integer|min:0',
                'stok_tabung_isi'  => 'sometimes|integer|min:0',
                'stok_tabung_kosong' => 'sometimes|integer|min:0',
            ];
        }

        // Rules untuk create (semua field required)
        return [
            'nama_barang'      => 'required|string|max:150',
            'kapasitas'        => 'required|string|max:50',
            'harga_jual'       => 'required|integer|min:0',
            'stok_tabung_isi'  => 'required|integer|min:0',
            'stok_tabung_kosong' => 'required|integer|min:0',
        ];
    }
}
