<?php

namespace App\Support\Recad;

final class Format
{
    private function __construct()
    {
    }

    public static function normalizeMatricula(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = preg_replace('/\\D+/', '', trim($value));
        if (!$v) {
            return null;
        }

        return str_pad($v, 8, '0', STR_PAD_LEFT);
    }
}

