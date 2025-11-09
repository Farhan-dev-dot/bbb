<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransaksipengirimanRequest extends FormRequest
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
        return [
            'id_barang' => 'required',
            'id_customer' => 'required|integer',
            'tanggal_pengiriman' => 'required|date',
            'lokasi_pengiriman' => 'required|string|max:255',
            'penerima' => 'required|string|max:255',
            'tabung_isi' => 'nullable|integer|min:0',
            'tabung_kosong' => 'nullable|integer|min:0',
            'pinjam_tabung' => 'nullable|integer|min:0',
            'total_harga' => 'nullable|integer|min:0',
            'keterangan' => 'nullable|string|max:500',
        ];
    }
}
