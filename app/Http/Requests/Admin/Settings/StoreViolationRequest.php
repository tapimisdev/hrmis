<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreViolationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'violation_type' => $this->canonicalViolationType((string) $this->input('violation_type')),
            'evaluation_period' => match ($this->input('evaluation_period')) {
                'Jan-Jun or Jul-Dec' => 'Jan–Jun or Jul–Dec',
                'Jan-Dec' => 'Jan–Dec',
                'Every FC day' => 'Count monthly',
                default => $this->input('evaluation_period'),
            },
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'violation_type' => [
                'required',
                'string',
                Rule::in([
                    'Tardiness / Late',
                    'Habitual Tardiness',
                    'Habitual Tardiness - Consecutive',
                    'Undertime',
                    'Frequent Undertime',
                    'Frequent Undertime - Consecutive',
                    'Unauthorized Absence',
                    'Habitual Absenteeism',
                    'Habitual Absenteeism - Consecutive',
                    'Discrepancy / Missing Timelog',
                    'Missed Break Log',
                ]),
            ],
            'rule_trigger' => ['required', 'string', 'max:1000'],
            'evaluation_period' => [
                'required',
                'string',
                Rule::in([
                    'Count monthly',
                    'Jan–Jun or Jul–Dec',
                    'Jan–Dec',
                    'Daily / Monthly',
                    'Per incident',
                ]),
            ],
            'action_name' => ['required', 'string', 'max:100'],
            'monthly_threshold' => ['required', 'numeric', 'min:0.01'],
            'threshold' => ['required', 'integer', 'min:1'],
        ];
    }

    private function canonicalViolationType(string $violationType): string
    {
        $normalized = str($violationType)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->upper()
            ->toString();

        return match ($normalized) {
            'TARDINESS / LATE', 'TARDINESS/LATE', 'LATE', 'LATES' => 'Tardiness / Late',
            'HABITUAL TARDINESS' => 'Habitual Tardiness',
            'HABITUAL TARDINESS - CONSECUTIVE', 'HABITUAL TARDINESS-CONSECUTIVE' => 'Habitual Tardiness - Consecutive',
            'UNDERTIME' => 'Undertime',
            'FREQUENT UNDERTIME' => 'Frequent Undertime',
            'FREQUENT UNDERTIME - CONSECUTIVE', 'FREQUENT UNDERTIME-CONSECUTIVE' => 'Frequent Undertime - Consecutive',
            'UNAUTHORIZED ABSENCE', 'ABSENCE', 'ABSENCES' => 'Unauthorized Absence',
            'HABITUAL ABSENTEEISM' => 'Habitual Absenteeism',
            'HABITUAL ABSENTEEISM - CONSECUTIVE', 'HABITUAL ABSENTEEISM-CONSECUTIVE' => 'Habitual Absenteeism - Consecutive',
            'DISCREPANCY / MISSING TIMELOG', 'DISCREPANCY/MISSING TIMELOG', 'MISSING TIMELOG', 'MISSING TIMELOGS', 'INCOMPLETE TIMELOGS' => 'Discrepancy / Missing Timelog',
            'MISSED BREAK LOG', 'MISSED BREAK', 'MISSING BREAK LOG', 'MISSING BREAK' => 'Missed Break Log',
            default => $violationType,
        };
    }
}
