<?php

namespace Domain\Transaction\Exception;

class MissingTickerTrading212ImportException extends Trading212ImportRowValidationException
{
    protected static function defaultMessage(): string
    {
        return 'The import file contains a trade row without a ticker.';
    }
}
