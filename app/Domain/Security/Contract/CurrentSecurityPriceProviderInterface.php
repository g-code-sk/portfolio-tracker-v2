<?php

namespace Domain\Security\Contract;

use Domain\Security\Data\CurrentSecurityPriceData;
use Domain\Security\Data\CurrentSecurityPriceLookupInputData;

interface CurrentSecurityPriceProviderInterface
{
    public function fetchCurrentPriceData(CurrentSecurityPriceLookupInputData $lookup): ?CurrentSecurityPriceData;
}
