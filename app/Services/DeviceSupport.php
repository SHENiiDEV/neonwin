<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceSupport
{
    public static function isMobile(Request $request): bool
    {
        return $request->header('Sec-CH-UA-Mobile') === '?1'
            || (bool) preg_match('/Android|iPhone|iPad|iPod|Mobile/i', (string) $request->userAgent());
    }
}
