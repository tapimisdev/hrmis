<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreLeaveApplication extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if (is_string($this->selectedDates)) {
            $this->merge([
                'selectedDates' => json_decode($this->selectedDates, true) ?? [],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'user_id'       => ['nullable', 'exists:users,id'],
            'leave_id'      => ['required', 'exists:leaves,id'],
            'reason'        => ['required', 'string', 'max:500'],
            'selectedDates' => ['required', 'array', 'min:1'],
            'selectedDates.*.date'  => ['required', 'date'],
            'selectedDates.*.shift' => ['required', 'in:morning,afternoon,wholeday'],

            'attachments'   => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:2048'],

            'approvers'     => ['nullable', 'array', 'min:1'],
            'approvers.*'   => ['nullable', 'array', 'min:1'],
            'approvers.*.*' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_id.required' => 'leave type is required.',
            'reason.required' => 'Reason for leave is required.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date must be today or later.',
            'end_date.after_or_equal' => 'End date must be after or same as start date.',
            'end_date.required' => 'End date is required.',
            'leave_applications.unique' => 'This leave request already exists.',

            'attachment.file' => 'The attachment must be a file.',
            'attachment.mimes' => 'The attachment must be a PDF, JPG, PNG, DOC, or DOCX file.',
            'attachment.max' => 'The attachment size must not exceed 2MB.',
        ];
    }
}
