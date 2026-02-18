<?php

namespace App\Http\Requests\Employee;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'mode_of_transport' => ['required', 'string', 'max:100'],
            'estimated_expense' => ['nullable', 'numeric', 'min:0'],
            'charge_to' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:500'],
            
            'attachments'   => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:8192'],

            // 'approvers'     => ['required', 'array', 'min:1'],
            // 'approvers.*'   => ['required', 'array', 'min:1'],
            // 'approvers.*.*' => ['required', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {

            $errors = $validator->errors();

            // Normalize attachments.* → attachments
            if (
                $errors->has('attachments') ||
                collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'attachments.'))
            ) {
                $messages = [];

                foreach ($errors->keys() as $key) {
                    if ($key === 'attachments' || str_starts_with($key, 'attachments.')) {
                        $messages = array_merge($messages, $errors->get($key));
                        $errors->forget($key);
                    }
                }

                $errors->add('attachments', array_unique($messages));
            }
        });
    }
}
