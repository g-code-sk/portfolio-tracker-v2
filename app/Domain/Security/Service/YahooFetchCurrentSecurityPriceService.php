<?php

namespace Domain\Security\Service;

use Carbon\CarbonImmutable;
use Domain\Security\Contract\CurrentSecurityPriceProviderInterface;
use Domain\Security\Data\CurrentSecurityPriceData;
use Domain\Security\Data\CurrentSecurityPriceLookupInputData;
use Domain\Security\Enums\SecurityDataProviderCode;
use GuzzleHttp\Exception\GuzzleException;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\Exception\ApiException;
use Scheb\YahooFinanceApi\Results\Quote;

class YahooFetchCurrentSecurityPriceService implements CurrentSecurityPriceProviderInterface
{
    public function __construct(
        private readonly ApiClient $apiClient,
    ) {}

    public function fetchCurrentPriceData(CurrentSecurityPriceLookupInputData $lookup): ?CurrentSecurityPriceData
    {
        try {
            $quote = $this->apiClient->getQuote($lookup->ticker);
        } catch (ApiException|GuzzleException $exception) {

            return null;
        }

        if (! $quote instanceof Quote) {
            return null;
        }

        $price = $quote->getRegularMarketPrice();

        if (! $this->hasPrice($price)) {
            return null;
        }

        $currency = $quote->getCurrency();

        if (! $this->hasCurrency($currency)) {
            return null;
        }

        return new CurrentSecurityPriceData(
            ticker: $lookup->ticker,
            price: number_format($price, 10, '.', ''),
            currency: strtoupper($currency),
            quotedAt: $this->resolveQuotedAt($quote),
            providerCode: SecurityDataProviderCode::Yahoo,
        );
    }

    private function hasPrice(?float $price): bool
    {
        return is_numeric($price);
    }

    private function hasCurrency(?string $currency): bool
    {
        return is_string($currency) && $currency !== '';
    }

    private function resolveQuotedAt(Quote $quote): CarbonImmutable
    {
        $quotedAt = $quote->getRegularMarketTime();

        if ($quotedAt === null) {
            return CarbonImmutable::now('UTC');
        }

        return CarbonImmutable::instance($quotedAt);
    }
}
