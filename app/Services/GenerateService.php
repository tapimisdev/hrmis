<?php

namespace App\Services;

class GenerateService {

    public static function filename(string $mode, ?string $prefix = null) {
        if($mode == 'randomized') {
            $unique = md5(time());
            return $unique;
        }
    }

}