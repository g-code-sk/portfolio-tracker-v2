<?php

namespace Domain\Security\Service;

use App\Support\ApplicationConfig;
use Domain\Security\Contract\CurrentSecurityPriceProviderInterface;
use Domain\Security\Data\CurrentSecurityPriceData;
use Domain\Security\Data\FinnhubQuoteData;
use Domain\Security\Data\FinnhubSymbolSearchHitData;
use Domain\Security\Enums\SecurityDataProviderCode;
use Finnhub\Api\DefaultApi;
use Throwable;

class FinnhubCurrentSecurityPriceProvider implements CurrentSecurityPriceProviderInterface
{
    public function __construct(
        private readonly ApplicationConfig $applicationConfig,
        private readonly DefaultApi $finnhubClient,
        private readonly FinnhubSymbolSearchHitMatcher $symbolSearchHitMatcher,
    ) {}

    public function fetchCurrentPriceData(string $ticker, ?string $name = null, ?string $isin = null): ?CurrentSecurityPriceData
    {
        $normalizedTicker = trim($ticker);

        if ($normalizedTicker === '') {
            return null;
        }

        $apiKey = $this->applicationConfig->getFinnhubApiKey();

        if ($apiKey === null) {
            return null;
        }

        $tickerUpper = strtoupper($normalizedTicker);
        $normalizedName = $name !== null ? trim($name) : null;
        $normalizedIsin = $isin !== null ? strtoupper(trim($isin)) : null;

        if ($normalizedIsin === '') {
            $normalizedIsin = null;
        }

        $symbolForQuote = $normalizedTicker;

        $quote = $this->fetchQuote($symbolForQuote);

        if ($quote === null || $quote->isEmpty()) {
            $resolved = $this->resolveSymbolViaSearch($tickerUpper, $normalizedName, $normalizedIsin);

            if ($resolved === null) {
                return null;
            }

            $symbolForQuote = $resolved;
            $quote = $this->fetchQuote($symbolForQuote);

            if ($quote === null || $quote->isEmpty()) {
                return null;
            }
        }

        if (! $quote->hasPrice()) {
            return null;
        }

        $currency = $this->fetchProfileCurrency($symbolForQuote);

        if ($currency === null) {
            return null;
        }

        return new CurrentSecurityPriceData(
            ticker: $normalizedTicker,
            price: $quote->getFormattedCurrentPrice(),
            currency: strtoupper($currency),
            quotedAt: $quote->getQuotedAt(),
            providerCode: SecurityDataProviderCode::Finnhub,
        );
    }

    private function fetchQuote(string $symbol): ?FinnhubQuoteData
    {
        try {
            $quote = $this->finnhubClient->quote($symbol);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($quote)) {
            return null;
        }

        return FinnhubQuoteData::fromPayload($quote);
    }

    private function resolveSymbolViaSearch(string $tickerUpper, ?string $normalizedName, ?string $normalizedIsin): ?string
    {
        /** @var array<int, string> $queries */
        $queries = [];

        if ($normalizedIsin !== null) {
            $queries[] = $normalizedIsin;
        }

        if ($normalizedName !== null && $normalizedName !== '') {
            $queries[] = $normalizedName;
        }

        $queries[] = $tickerUpper;

        foreach ($queries as $query) {
            $hits = $this->fetchSearchHits($query);
            $symbols = $this->symbolSearchHitMatcher->matchSymbolsFromHits($hits, $tickerUpper, $normalizedIsin, $normalizedName);

            if (count($symbols) === 1) {
                return $symbols[0];
            }

            if (count($symbols) > 1) {
                return null;
            }
        }

        return null;
    }

    /**
     * @return list<FinnhubSymbolSearchHitData>
     */
    private function fetchSearchHits(string $query): array
    {
        try {
            $payload = $this->finnhubClient->symbolSearch($query);
        } catch (Throwable) {
            return [];
        }

        if (! is_array($payload)) {
            return [];
        }

        $result = data_get($payload, 'result');

        if (! is_array($result)) {
            return [];
        }

        /** @var list<FinnhubSymbolSearchHitData> $hits */
        $hits = [];

        foreach ($result as $row) {
            $hit = FinnhubSymbolSearchHitData::tryFromRow($row);

            if ($hit !== null) {
                $hits[] = $hit;
            }
        }

        return $hits;
    }

    private function fetchProfileCurrency(string $symbol): ?string
    {
        try {
            $profilePayload = $this->finnhubClient->companyProfile2($symbol);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($profilePayload)) {
            return null;
        }

        $currency = data_get($profilePayload, 'currency');

        if (! is_string($currency) || $currency === '') {
            return null;
        }

        return $currency;
    }
}
