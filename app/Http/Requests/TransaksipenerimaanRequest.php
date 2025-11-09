<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransaksipenerimaanRequest extends FormRequest
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
            'id_barang' => 'required|integer',
            'tanggal_penerimaan' => 'required|date',
            'tabung_isi' => 'sometimes|integer',
            'tabung_kosong' => 'sometimes|integer',
            'pinjam_tabung' => 'sometimes|integer',
        ];
    }
}
