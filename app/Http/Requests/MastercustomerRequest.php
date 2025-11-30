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
        $customerId = null;

        $segments = $this->segments();
        $masterCustomerIndex = array_search('master-customer', $segments);
        if ($masterCustomerIndex !== false && isset($segments[$masterCustomerIndex + 1])) {
            $customerId = $segments[$masterCustomerIndex + 1];
        }
        return [
            'kode_customer' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('dbo_customer', 'kode_customer')
                    ->ignore($customerId, 'id_customer')
            ],
            'nama_customer' => 'required|string|max:150',
            'alamat' => 'sometimes|nullable|string|max:255',
            'telepon' => 'sometimes|nullable|string|max:20',
        ];
    }
}
