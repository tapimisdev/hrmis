<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveApplication extends FormRequest
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
            'leave_type' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:500'],
            'days' => ['required', 'integer'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            // File attachment (optional but must be valid if uploaded)
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:2048'],

            // Prevent duplicate leave applications with same dates and type
            Rule::unique('leave_applications')->where(function ($query) {
                return $query->where('employee_id', $this->employee_id)
                    ->where('leave_type', $this->leave_type)
                    ->where('start_date', $this->start_date)
                    ->where('end_date', $this->end_date);
            }),
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type.required' => 'Leave type is required.',
            'reason.required' => 'Reason for leave is required.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date must be today or later.',
            'end_date.after_or_equal' => 'End date must be after or same as start date.',
            'end_date.required' => 'End date is required.',
            'leave_applications.unique' => 'This leave request already exists.',

            // Attachment messages
            'attachment.file' => 'The attachment must be a file.',
            'attachment.mimes' => 'The attachment must be a PDF, JPG, PNG, DOC, or DOCX file.',
            'attachment.max' => 'The attachment size must not exceed 2MB.',
        ];
    }

}
