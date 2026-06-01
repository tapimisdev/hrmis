<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLtoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'lto_no' => ['required', 'string', 'max:255'],
            'shift' => ['required', 'in:morning,afternoon,wholeday'],
            'is_hazardous' => ['required', 'in:yes,no'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'attachments' => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $errors = $validator->errors();

            if (
                $errors->has('attachments') ||
                collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'attachments.'))
            ) {
                $messages = [];

                foreach ($errors->keys() as $key) {
                    if ($key === 'attachments' || str_starts_with($key, 'attachments.')) {
                        $messages = array_merge($messages, $errors->get($key));
                        $errors->forget($key);
                    }
                }

                $errors->add('attachments', array_values(array_unique($messages)));
            }
        });
    }
}
