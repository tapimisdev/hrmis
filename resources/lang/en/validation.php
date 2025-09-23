<?php

return [
    'required' => '*required',
    'exists'   => '*already exists',
    'date'     => '*invalid date',
    'email'    => '*invalid email',
    'unique'   => '*already taken',
    'numeric'  => '*must be a number',
    'string'   => '*must be text',
    'boolean'  => '*must be true or false',
    'integer'  => '*must be an integer',
    'digits'   => '*must be :digits digits',
    'digits_between' => '*must be between :min and :max digits',
    'regex'    => '*invalid format',
    'confirmed'=> '*confirmation does not match',
    'in'       => '*must be one of: :values',
    'not_in'   => '*invalid selection',
    'same'     => '*must match :other',

    // Min rules
    'min.string'  => '*too short (minimum :min characters)',
    'min.numeric' => '*must be at least :min',
    'min.array'   => '*must have at least :min items',
    'min.file'    => '*file must be at least :min kilobytes',

    // Max rules
    'max.string'  => '*too long (maximum :max characters)',
    'max.numeric' => '*must not be greater than :max',
    'max.array'   => '*must not have more than :max items',
    'max.file'    => '*file must not be larger than :max kilobytes',

    // File & MIME rules
    'file'     => '*invalid file',
    'mimes'    => '*only accepts :values',
    'mimetypes'=> '*invalid file type (must be :values)',
    'image'    => '*must be an image',
    'dimensions' => '*invalid image dimensions',
    'size'     => '*file size must be exactly :size kilobytes',

    'after_or_equal' => '*must be a date after or now :date',
    'after'          => '*must be a date after :date',
];
