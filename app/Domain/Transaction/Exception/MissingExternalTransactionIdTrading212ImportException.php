<?php

namespace Domain\Transaction\Exception;

class MissingExternalTransactionIdTrading212ImportException extends Trading212ImportRowValidationException
{
    protected static function defaultMessage(): string
    {
        return 'The import file contains a trade row without an external transaction id.';
    }
}
