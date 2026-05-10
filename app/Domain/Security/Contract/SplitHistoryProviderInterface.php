<?php

namespace Domain\Security\Contract;

use DateTimeInterface;
use Domain\Security\Data\SecuritySplitEventData;

interface SplitHistoryProviderInterface
{
    /**
     * @return list<SecuritySplitEventData>|null null when the remote fetch failed; empty list when successful but no splits in range
     */
    public function fetchSplitEvents(string $ticker, DateTimeInterface $startInclusive, DateTimeInterface $endInclusive): ?array;
}
