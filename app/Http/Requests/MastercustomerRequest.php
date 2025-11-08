<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MastercustomerRequest extends FormRequest
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
            'kode_customer' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dbo_customer', 'kode_customer')
                    ->ignore(optional($this->route('mcustomer'))->id_customer, 'id_customer')
            ],
            'nama_customer' => 'required|string|max:150',
            'alamat' => 'sometimes|nullable|string|max:255',
            'no_telp' => 'sometimes|nullable|string|max:20',
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('dbo_customer', 'email')
                    ->ignore(optional($this->route('mcustomer'))->id_customer, 'id_customer')
            ],
        ];
    }
}
