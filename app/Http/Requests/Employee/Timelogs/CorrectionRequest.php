<?php

namespace App\Http\Requests\Employee\Timelogs;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
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
            'isDirectlyApproved' => ['nullable'],
            'date' => ['required', 'date'],
            'time_in' => ['required', 'date_format:H:i:s'],
            'break_out' => [
                'nullable',
                'required_with:break_in',
                'date_format:H:i:s',
                'after:time_in',
            ],
            'break_in' => [
                'nullable',
                'required_with:break_out',
                'date_format:H:i:s',
                'after:break_out',
            ],
            'time_out' => ['required', 'date_format:H:i:s', 'after:time_in'],
            'overtime_in' => ['nullable', 'date_format:H:i:s', 'after:break_out'],
            'overtime_out' => [
                'required_with:overtime_in',   
                'nullable',
                'date_format:H:i:s',
                'after:overtime_in'
            ],
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'], 
            'concern' => ['required', 'in:OO,F,IE'],
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }
}
