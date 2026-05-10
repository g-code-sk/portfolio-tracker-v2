<?php

namespace Domain\Security\Service;

use Domain\Security\Contract\SplitHistoryProviderInterface;
use Domain\Security\Data\SecuritySplitEventData;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\Exception\ApiException;

class YahooSplitHistoryProvider implements SplitHistoryProviderInterface
{
    public function __construct(
        private readonly ApiClient $apiClient,
    ) {}

    /**
     * @return list<SecuritySplitEventData>|null
     */
    public function fetchSplitEvents(string $ticker, \DateTimeInterface $startInclusive, \DateTimeInterface $endInclusive): ?array
    {
        try {
            $rows = $this->apiClient->getHistoricalSplitData($ticker, $startInclusive, $endInclusive);
        } catch (ApiException|GuzzleException $exception) {
            Log::error('Yahoo split history fetch failed.', [
                'ticker' => $ticker,
                'exception' => $exception,
            ]);

            return null;
        }

        $events = [];

        foreach ($rows as $split) {
            $events[] = SecuritySplitEventData::fromYahooSplitData($split);
        }

        return $events;
    }
}
