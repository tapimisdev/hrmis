<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now, allow all authenticated users
        // You can customize this with policies or roles
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
            // Basic Info
            'employee_no' => 'required|string|max:50',
            'biometrics_id' => 'nullable|string|max:50',
            'firstname' => 'required|string|max:100',
            'middlename' => 'nullable|string|max:100',
            'lastname' => 'required|string|max:100',
            'suffix' => 'nullable|string|max:10',

            // Personal Details
            'birthday' => 'required|date',
            'age' => 'nullable|integer|min:0',
            'civil_status' => 'required|string|in:Single,Married,Widowed,Separated',
            'sex' => 'required|string|in:Male,Female',
            'blood_type' => 'nullable|string|max:3',

            // Present Address
            'present_block' => 'nullable|string|max:50',
            'present_street' => 'nullable|string|max:100',
            'present_subdivision' => 'nullable|string|max:100',
            'present_barangay' => 'nullable|string|max:100',
            'present_city' => 'nullable|string|max:100',
            'present_province' => 'nullable|string|max:100',
            'present_zip' => 'nullable|string|max:20',

            // Permanent Address
            'permanent_block' => 'nullable|string|max:50',
            'permanent_street' => 'nullable|string|max:100',
            'permanent_subdivision' => 'nullable|string|max:100',
            'permanent_barangay' => 'nullable|string|max:100',
            'permanent_city' => 'nullable|string|max:100',
            'permanent_province' => 'nullable|string|max:100',
            'permanent_zip' => 'nullable|string|max:20',

            // Government IDs
            'gsis_no' => 'nullable|string|max:50',
            'pagibig_no' => 'nullable|string|max:50',
            'philhealth_no' => 'nullable|string|max:50',
            'sss_no' => 'nullable|string|max:50',
            'tin_no' => 'nullable|string|max:50',
            'philsys_no' => 'nullable|string|max:50',

            // Profile Image
            'profile' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // max 2MB
        ];
    }

    /**
     * Optional: Custom messages for validation
     */
    public function messages(): array
    {
        return [
            'firstname.required' => 'First name is required.',
            'lastname.required' => 'Last name is required.',
            'birthday.required' => 'Birthday is required.',
            'civil_status.required' => 'Civil status is required.',
            'sex.required' => 'Sex is required.',
            'profile.image' => 'Profile must be an image.',
            'profile.mimes' => 'Profile must be a JPG or PNG file.',
            'profile.max' => 'Profile cannot exceed 2MB.',
        ];
    }
}
