<?php

namespace Domain\Security\Data;

use Carbon\CarbonImmutable;
use Domain\Security\Enums\SecurityDataProviderCode;

final readonly class CurrentSecurityPriceData
{
    public function __construct(
        public string $ticker,
        public string $price,
        public string $currency,
        public CarbonImmutable $quotedAt,
        public SecurityDataProviderCode $providerCode,
    ) {}
}
