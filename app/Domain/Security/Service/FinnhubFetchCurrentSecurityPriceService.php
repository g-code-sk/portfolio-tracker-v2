<?php

namespace Domain\Security\Service;

use App\Support\ApplicationConfig;
use Domain\Security\Contract\CurrentSecurityPriceProviderInterface;
use Domain\Security\Data\CurrentSecurityPriceData;
use Domain\Security\Data\CurrentSecurityPriceLookupInputData;
use Domain\Security\Data\FinnhubQuoteData;
use Domain\Security\Data\FinnhubSymbolSearchHitData;
use Domain\Security\Enums\SecurityDataProviderCode;
use Finnhub\Api\DefaultApi;
use Throwable;

class FinnhubFetchCurrentSecurityPriceService implements CurrentSecurityPriceProviderInterface
{
    public function __construct(
        private readonly ApplicationConfig $applicationConfig,
        private readonly DefaultApi $finnhubClient,
    ) {}

    public function fetchCurrentPriceData(CurrentSecurityPriceLookupInputData $lookup): ?CurrentSecurityPriceData
    {
        $apiKey = $this->applicationConfig->getFinnhubApiKey();

        if ($apiKey === null) {
            return null;
        }

        $symbolForQuote = $lookup->ticker;

        $quote = $this->fetchQuote($symbolForQuote);

        if ($quote === null || $quote->isEmpty()) {
            $resolved = $this->resolveSymbolViaSearch($lookup);

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
            ticker: $lookup->ticker,
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

    private function resolveSymbolViaSearch(CurrentSecurityPriceLookupInputData $lookup): ?string
    {
        /** @var array<int, string> $queries */
        $queries = [];

        if ($lookup->normalizedIsin !== null) {
            $queries[] = $lookup->normalizedIsin;
        }

        if ($lookup->hasNonEmptyNormalizedDisplayName()) {
            $queries[] = $lookup->normalizedDisplayName;
        }

        $queries[] = $lookup->tickerUpper;

        foreach ($queries as $query) {
            $hits = $this->fetchSearchHits($query);
            $symbols = $this->matchingCanonicalSymbolsFromHits($hits, $lookup);

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
     * @param  list<FinnhubSymbolSearchHitData>  $hits
     * @return list<string>
     */
    private function matchingCanonicalSymbolsFromHits(array $hits, CurrentSecurityPriceLookupInputData $lookup): array
    {
        $passedHits = [];

        foreach ($hits as $hit) {
            if (! $hit->matchSymbol($lookup)) {
                continue;
            }

            $passedHits[] = $hit;
        }

        $shouldDisambiguateByName = count($passedHits) > 1 && $lookup->hasNonEmptyNormalizedDisplayName();

        if ($shouldDisambiguateByName) {
            $passedHits = array_values(array_filter(
                $passedHits,
                fn (FinnhubSymbolSearchHitData $hit): bool => $hit->matchesNormalizedNameTokens($lookup->normalizedDisplayName),
            ));
        }

        return collect($passedHits)
            ->pluck('symbol')
            ->unique()
            ->values()
            ->all();
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

        if (! $this->hasNonEmptyProfileCurrency($currency)) {
            return null;
        }

        return $currency;
    }

    private function hasNonEmptyProfileCurrency(mixed $currency): bool
    {
        return is_string($currency) && $currency !== '';
    }
}
