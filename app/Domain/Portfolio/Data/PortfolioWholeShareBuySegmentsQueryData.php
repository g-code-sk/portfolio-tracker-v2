<?php

namespace Domain\Portfolio\Data;

use Spatie\LaravelData\Data;

class PortfolioWholeShareBuySegmentsQueryData extends Data
{
    public function __construct(
        public int $securityId,
        public int $currencyId,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'securityId' => ['required', 'integer', 'exists:securities,id'],
            'currencyId' => ['required', 'integer', 'exists:currencies,id'],
        ];
    }
}
