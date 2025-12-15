<?php

namespace App\Http\Requests\Admin\SLAPay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
            'label' => 'required|string|max:50',
            'month' => 'required|date',
            'employment_type_id' => 'required',
            'approved_by' => ['required', 'array'],
            'approved_by.*' => ['array', 'min:1'],
            'approved_by.*.*' => ['integer', 'exists:users,id'],
        ];
    }

}
