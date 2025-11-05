<?php

use Illuminate\Support\Str;

// Can use in any part of the application to generate unique codes
if (!function_exists('generateNo')) {
    function generateNo($prefix = 'NO-', $length = 6)
    {
        // Example: NO-20251022-AB1234
        $datePart = now()->format('Ymd');
        $randomPart = strtoupper(Str::random($length));

        return $prefix . $datePart . '-' . $randomPart;
    }
}
