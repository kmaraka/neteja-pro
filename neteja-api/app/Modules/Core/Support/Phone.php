<?php

namespace App\Modules\Core\Support;

class Phone
{
    /** "07 00 00 00 00" donne "+2250700000000". */
    public static function normalize(string $raw): string
    {
        $n = preg_replace('/[^\d+]/', '', $raw);

        if (str_starts_with($n, '00')) {
            $n = '+'.substr($n, 2);
        }

        if (! str_starts_with($n, '+')) {
            if (str_starts_with($n, '225') && strlen($n) === 13) {
                $n = '+'.$n;
            } elseif (strlen($n) === 10) {
                $n = '+225'.$n; // numéros ivoiriens à 10 chiffres
            }
        }

        return $n;
    }
}
