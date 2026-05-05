<?php

namespace Domain\Portfolio\Data;

use Spatie\LaravelData\Data;

class PortfolioTransactionsQueryData extends Data
{
    public function __construct(
        public ?int $securityId,
        public ?int $currencyId,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'securityId' => ['nullable', 'integer', 'exists:securities,id'],
            'currencyId' => ['nullable', 'integer', 'exists:currencies,id'],
        ];
    }
}
