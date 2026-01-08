<?php

namespace App\Http\Requests\Admin\Modules;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleTabEmployeeRequest extends FormRequest
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
        return   [
            'module_tab_id' => 'nullable|exists:module_tabs,id',
            'employee_no' => 'required|exists:employee_information,employee_no',
            'year' => 'required|numeric',
            'month' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'module_tab_id.exists' => 'The selected module tab does not exist in the database.',
            'employee_no.exists' => 'The selected employee number does not exist.',
        ];
    }

}
