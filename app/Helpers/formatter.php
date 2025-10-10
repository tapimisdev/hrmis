<?php

if (!function_exists('ordinal')) {
    function ordinal($num) {
        $num = (int)$num;
        $suffix = 'th';

        if (($num % 100) >= 11 && ($num % 100) <= 13) {
            $suffix = 'th';
        } else {
            switch ($num % 10) {
                case 1:
                    $suffix = 'st';
                    break;
                case 2:
                    $suffix = 'nd';
                    break;
                case 3:
                    $suffix = 'rd';
                    break;
            }
        }

        return $num . $suffix;
    }
}
