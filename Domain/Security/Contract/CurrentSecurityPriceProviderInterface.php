<?php

namespace Domain\Security\Contract;

use Domain\Security\Data\CurrentSecurityPriceData;

interface CurrentSecurityPriceProviderInterface
{
    public function fetchCurrentPriceData(string $ticker, ?string $name = null, ?string $isin = null): ?CurrentSecurityPriceData;
}
