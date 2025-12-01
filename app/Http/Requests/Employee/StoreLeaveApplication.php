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

            // 'approvers'     => ['nullable', 'array', 'min:1'],
            // 'approvers.*'   => ['nullable', 'array', 'min:1'],
            // 'approvers.*.*' => ['nullable', 'exists:users,id'],
        ];
    }
}
