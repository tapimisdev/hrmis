<?php

namespace App\Http\Requests\Admin\LongevityPay;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'required|string|max:50',
            'month' => 'required|date',
            'employment_type_id' => 'required',
        ];
    }
}
