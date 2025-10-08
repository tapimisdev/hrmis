<?php

namespace App\Http\Requests\Admin\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryRequest extends FormRequest
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
            'payroll_no'       => 'required|string|max:50|unique:payroll_salary,payroll_no',
            'cutoff'           => 'required|string|max:100',
            'period_covered'   => 'required|string|max:100',
            'no_employee'      => 'required|integer|min:1',
            'gross_amount'     => 'required|numeric|min:0',
            'deduction_amount' => 'required|numeric|min:0',
            'netpay_amount'    => 'required|numeric|min:0',
            'payroll_date'     => 'required|date',
            'status'           => 'required|in:draft,pending,approved,for_releasing,completed,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'payroll_no.required'       => 'The payroll number is required.',
            'payroll_no.unique'         => 'This payroll number already exists.',
            'cutoff.required'           => 'Please provide the cutoff period.',
            'period_covered.required'   => 'Please provide the period covered.',
            'no_employee.required'      => 'Number of employees is required.',
            'gross_amount.required'     => 'Gross amount is required.',
            'deduction_amount.required' => 'Deduction amount is required.',
            'netpay_amount.required'    => 'Net pay amount is required.',
            'payroll_date.required'     => 'Payroll date is required.',
            'status.in'                 => 'Invalid status value provided.',
        ];
    }
}
