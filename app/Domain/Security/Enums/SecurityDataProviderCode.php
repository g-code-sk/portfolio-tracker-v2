<?php

namespace Domain\Security\Enums;

enum SecurityDataProviderCode: string
{
    case Yahoo = 'yahoo';

    case Finnhub = 'finnhub';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Yahoo->value,
            self::Finnhub->value,
        ];
    }
}
