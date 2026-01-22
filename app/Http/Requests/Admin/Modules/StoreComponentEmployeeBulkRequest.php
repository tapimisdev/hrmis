<?php

namespace App\Http\Requests\Admin\Modules;

use Illuminate\Foundation\Http\FormRequest;

class StoreComponentEmployeeBulkRequest extends FormRequest
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
            'module_tab' => 'required|exists:payroll_components,slug',
            'id'        => 'required|exists:payroll_components,id',
            'year' => ['required', 'integer', 'digits:4', 'min:2000', 'max:2100'],
            'employee_nos' => 'required|string',
            'from_month' => ['required', 'integer', 'between:1,12'],
            'to_month'   => ['required', 'integer', 'between:1,12', 'gte:from_month'],
            'amount_type'  => 'required|in:fixed,percent',
            'amount'       => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->input('amount_type') === 'percent' && $value > 100) {
                        $fail('Percentage amount cannot exceed 100%.');
                    }
                },
            ],
        ];
    }
}
