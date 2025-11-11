<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransaksiRequest extends FormRequest
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
            'id_customer' => 'required',
            'tanggal_pengiriman' => 'required|date',
            'pengirim' => 'nullable|string|max:225', // Sesuai database varchar(225)
            'alamat' => 'nullable|string|max:255',
            'jumlah_isi' => 'required|integer|min:0',
            'jumlah_kosong' => 'required|integer|min:0',
            'pinjam_tabung' => 'nullable|integer|min:0',
            'harga_satuan' => 'required|numeric|min:0',
            'keterangan' => 'required|string|max:150',
            'status' => 'required',

        ];
    }
}
