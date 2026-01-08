<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreOffsetApplication extends FormRequest
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
            'reason'        => ['required', 'string', 'max:500'],
            'selectedDates' => ['required', 'array', 'min:1'],
            'selectedDates.*.date'  => ['required', 'date'],
            'selectedDates.*.shift' => ['required', 'in:morning,afternoon,wholeday'],

            'attachments'   => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:8192'],

            // 'approvers'     => ['nullable', 'array', 'min:1'],
            // 'approvers.*'   => ['nullable', 'array', 'min:1'],
            // 'approvers.*.*' => ['nullable', 'exists:users,id'],
        ];
    }
}
