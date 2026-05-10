<?php

namespace Domain\Transaction\Enums;

enum TransactionTypeCode: string
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
