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
        return [
            'kode_barang' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dbo_barang', 'kode_barang')
                    ->ignore(optional($this->route('mbarang'))->id_barang, 'id_barang')
            ],

            'nama_barang'      => 'required|string|max:150',
            'harga_jual'       => 'required|integer|min:0',
            'stok'             => 'required|integer|min:0',
            'id_kategori' => 'sometimes|nullable',

        ];
    }
}
