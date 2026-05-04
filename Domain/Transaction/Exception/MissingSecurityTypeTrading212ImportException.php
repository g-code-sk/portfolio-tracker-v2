<?php

namespace Domain\Transaction\Exception;

class MissingSecurityTypeTrading212ImportException extends Trading212ImportRowValidationException
{
    protected static function defaultMessage(): string
    {
        return 'The import could not be processed because the security type for this trade is missing from the database.';
    }
}
