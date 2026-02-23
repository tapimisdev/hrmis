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

    protected function prepareForValidation()
    {
        if (is_string($this->selectedDates)) {
            $this->merge([
                'selectedDates' => json_decode($this->selectedDates, true) ?? [],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
