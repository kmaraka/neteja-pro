<?php

namespace App\Modules\Core\Support;

class Money
{
    /** 25000 devient « 25 000 FCFA ». */
    public static function fcfa(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
