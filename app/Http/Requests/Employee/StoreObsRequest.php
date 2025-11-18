<?php

namespace App\Http\Requests\Employee;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreObsRequest extends FormRequest
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
            'destination' => [
                'required',
                'string',
                'max:255',
            ],

            'purpose' => ['required', 'string', 'max:500'],

            'date_from' => ['required', 'date', 'after_or_equal:today'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],

            'time_out' => ['nullable', 'date_format:H:i'],
            'time_in' => ['nullable', 'date_format:H:i', 'after:time_out'],

            'mode_of_transport' => ['nullable', 'string', 'max:100'],
            'estimated_expense' => ['nullable', 'numeric', 'min:0'],
            'charge_to' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:500'],

            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:2048'],

            // 'approvers'     => ['required', 'array', 'min:1'],
            // 'approvers.*'   => ['required', 'array', 'min:1'],
            // 'approvers.*.*' => ['required', 'exists:users,id'],
        ];
    }

    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate date range vs time fields
            if ($this->date_from && $this->date_to && $this->time_out && $this->time_in) {
                $startDateTime = Carbon::parse($this->date_from . ' ' . $this->time_out);
                $endDateTime = Carbon::parse($this->date_to . ' ' . $this->time_in);

                if ($endDateTime->lessThan($startDateTime)) {
                    $validator->errors()->add('time_in', "Time in must be after time out.");
                }
            }
        });
    }
}
