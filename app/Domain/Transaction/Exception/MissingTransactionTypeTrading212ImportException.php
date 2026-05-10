<?php

namespace Domain\Transaction\Exception;

class MissingTransactionTypeTrading212ImportException extends Trading212ImportRowValidationException
{
    protected static function defaultMessage(): string
    {
        return 'The import could not be processed because the transaction type for this trade is missing from the database.';
    }
}
