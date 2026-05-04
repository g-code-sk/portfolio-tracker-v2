<?php

namespace Domain\Transaction\Exception;

class InvalidPriceCurrencyTrading212ImportException extends Trading212ImportRowValidationException
{
    protected static function defaultMessage(): string
    {
        return 'The import file contains an invalid 3-letter price currency.';
    }
}
