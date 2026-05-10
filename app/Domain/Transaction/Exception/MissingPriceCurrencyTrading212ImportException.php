<?php

namespace Domain\Transaction\Exception;

class MissingPriceCurrencyTrading212ImportException extends Trading212ImportRowValidationException
{
    protected static function defaultMessage(): string
    {
        return 'The import file contains a trade row with missing price currency.';
    }
}
