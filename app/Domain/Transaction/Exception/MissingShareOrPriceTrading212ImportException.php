<?php

namespace Domain\Transaction\Exception;

class MissingShareOrPriceTrading212ImportException extends Trading212ImportRowValidationException
{
    protected static function defaultMessage(): string
    {
        return 'The import file contains a trade row with missing share or price data.';
    }
}
