<?php

namespace Domain\Portfolio\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PortfolioResponseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
