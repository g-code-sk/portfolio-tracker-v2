<?php

namespace Domain\Security\Contract;

use Domain\Security\Data\CurrentSecurityPriceData;

interface CurrentSecurityPriceProviderInterface
{
    public function getCurrentPrice(string $ticker): ?CurrentSecurityPriceData;
}
