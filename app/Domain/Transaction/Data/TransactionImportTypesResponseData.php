<?php

namespace Domain\Transaction\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TransactionImportTypesResponseData extends Data
{
    /**
     * @param array<int, string> $importTypes
     */
    public function __construct(
        public array $importTypes,
    ) {}
}
