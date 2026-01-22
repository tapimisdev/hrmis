<?php

namespace App\Http\Requests\Admin\Modules;

use Illuminate\Foundation\Http\FormRequest;

class StorePhilhealthRequest extends FormRequest
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
        'computation' => [
            'required',
            function ($attribute, $value, $fail) {
                // remove spaces
                $value = trim((string) $value);

                $parts = array_map('trim', explode(',', $value));

                if (count($parts) !== 3) {
                    $fail('PhilHealth computation must be in the format: rate,floor,ceiling.');
                    return;
                }

                [$rate, $floor, $ceiling] = $parts;

                if (!is_numeric($rate) || !is_numeric($floor) || !is_numeric($ceiling)) {
                    $fail('PhilHealth computation values must all be numeric.');
                    return;
                }

                if ((float) $floor > (float) $ceiling) {
                    $fail('Floor amount must not be greater than the ceiling amount.');
                    return;
                }
            },
        ],

        'year' => ['required', 'integer', 'min:2000'],
        'module_tab' => ['required', 'exists:module_tabs,tab_slug'],
    ];
}

}
