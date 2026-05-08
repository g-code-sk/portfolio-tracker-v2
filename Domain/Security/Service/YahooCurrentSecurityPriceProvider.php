<?php

namespace Domain\Security\Service;

use Carbon\CarbonImmutable;
use Domain\Security\Contract\CurrentSecurityPriceProviderInterface;
use Domain\Security\Data\CurrentSecurityPriceData;
use Domain\Security\Enums\SecurityDataProviderCode;
use Illuminate\Support\Facades\Http;

class YahooCurrentSecurityPriceProvider implements CurrentSecurityPriceProviderInterface
{
    private const string QUOTE_URL = 'https://query1.finance.yahoo.com/v7/finance/quote';

    public function getCurrentPrice(string $ticker): ?CurrentSecurityPriceData
    {
        $normalizedTicker = trim($ticker);

        if ($normalizedTicker === '') {
            return null;
        }

        $response = Http::acceptJson()
            ->retry(2, 200)
            ->timeout(10)
            ->get(self::QUOTE_URL, [
                'symbols' => $normalizedTicker,
            ]);

        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        /** @var array<int, array<string, mixed>> $results */
        $results = data_get($payload, 'quoteResponse.result', []);
        /** @var array<string, mixed>|null $firstResult */
        $firstResult = $results[0] ?? null;

        if ($firstResult === null) {
            return null;
        }

        $price = data_get($firstResult, 'regularMarketPrice');

        if (! is_numeric($price)) {
            return null;
        }

        $currency = data_get($firstResult, 'currency');

        if (! is_string($currency) || $currency === '') {
            return null;
        }

        $quotedAtTimestamp = data_get($firstResult, 'regularMarketTime');

        if (is_numeric($quotedAtTimestamp)) {
            $quotedAt = CarbonImmutable::createFromTimestampUTC((int) $quotedAtTimestamp);
        } else {
            $quotedAt = CarbonImmutable::now('UTC');
        }

        return new CurrentSecurityPriceData(
            ticker: $normalizedTicker,
            price: number_format((float) $price, 10, '.', ''),
            currency: strtoupper($currency),
            quotedAt: $quotedAt,
            providerCode: SecurityDataProviderCode::Yahoo,
        );
    }
}
