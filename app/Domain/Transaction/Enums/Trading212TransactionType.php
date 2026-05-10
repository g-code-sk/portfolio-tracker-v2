<?php

namespace Domain\Transaction\Enums;

enum Trading212TransactionType: string
{
    case MarketBuy = 'Market buy';
    case MarketSell = 'Market sell';

    public static function isSupportedTradeAction(string $action): bool
    {
        return self::tryFrom($action) !== null;
    }
}
