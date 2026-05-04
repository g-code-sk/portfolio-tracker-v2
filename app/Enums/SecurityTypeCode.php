<?php

namespace App\Enums;

enum SecurityTypeCode: string
{
    case Buy = 'buy';
    case Sell = 'sell';

    public static function values(): array
    {
        return [
            self::Buy->value,
            self::Sell->value,
        ];
    }
}
