<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEarningRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('earnings', 'name')->ignore($this->route('earning')),
            ],
            'first_term' => 'required|numeric|min:0',
            'second_term' => 'required|numeric|min:0',
            'is_taxable' => 'required|boolean',
        ];
    }
}
