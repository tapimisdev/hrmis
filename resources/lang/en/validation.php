<?php

return [

    // Basic Rules
    'required' => 'This field is required.',
    'exists'   => 'The selected value already exists.',
    'date'     => 'Please provide a valid date.',
    'email'    => 'Please enter a valid email address.',
    'unique'   => 'This value has already been taken.',
    'numeric'  => 'Please enter a valid number.',
    'string'   => 'Please enter text.',
    'boolean'  => 'This field must be true or false.',
    'integer'  => 'Please enter a valid integer.',
    'digits'   => 'This must be exactly :digits digits.',
    'digits_between' => 'This must be between :min and :max digits.',
    'regex'    => 'The format is invalid.',
    'confirmed'=> 'The confirmation does not match.',
    'in'       => 'Please select a valid option.',
    'not_in'   => 'The selected option is invalid.',
    'same'     => 'This must match :other.',

    // Min rules
    'min.string'  => 'Must be at least :min characters.',
    'min.numeric' => 'Must be at least :min.',
    'min.array'   => 'Must have at least :min items.',
    'min.file'    => 'File must be at least :min kilobytes.',

    // Max rules
    'max.string'  => 'Must not exceed :max characters.',
    'max.numeric' => 'Must not be greater than :max.',
    'max.array'   => 'Must not have more than :max items.',
    'max.file'    => 'File must not be larger than :max kilobytes.',

    // File & MIME rules
    'file'        => 'Please upload a valid file.',
    'mimes'       => 'Allowed file types: :values.',
    'mimetypes'   => 'Invalid file type. Must be: :values.',
    'image'       => 'Please upload a valid image.',
    'dimensions'  => 'Invalid image dimensions.',
    'size'        => 'File size must be exactly :size kilobytes.',

    // Date rules
    'after_or_equal' => 'Date must be today or after :date.',
    'after'          => 'Date must be after :date.',
];
