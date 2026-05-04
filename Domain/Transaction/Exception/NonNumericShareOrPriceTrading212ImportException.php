<?php

namespace Domain\Transaction\Exception;

class NonNumericShareOrPriceTrading212ImportException extends Trading212ImportRowValidationException
{
    protected static function defaultMessage(): string
    {
        return 'The import file contains non-numeric share or price values.';
    }
}
