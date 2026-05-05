<?php

namespace Domain\Transaction\Exception;

class InvalidExecutionTimeTrading212ImportException extends Trading212ImportRowValidationException
{
    protected static function defaultMessage(): string
    {
        return 'The import file contains a trade row with a missing or unparseable Time value. Expected format: Y-m-d H:i:s (e.g. 2022-01-26 07:55:40).';
    }
}
