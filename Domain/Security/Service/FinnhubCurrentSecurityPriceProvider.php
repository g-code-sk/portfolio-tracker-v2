<?php

namespace Domain\Security\Service;

use App\Support\ApplicationConfig;
use Carbon\CarbonImmutable;
use Domain\Security\Contract\CurrentSecurityPriceProviderInterface;
use Domain\Security\Data\CurrentSecurityPriceData;
use Domain\Security\Enums\SecurityDataProviderCode;
use Illuminate\Support\Facades\Http;

class FinnhubCurrentSecurityPriceProvider implements CurrentSecurityPriceProviderInterface
{
    private const string QUOTE_URL = 'https://finnhub.io/api/v1/quote';

    private const string PROFILE_URL = 'https://finnhub.io/api/v1/stock/profile2';

    public function __construct(
        private readonly ApplicationConfig $applicationConfig
    ) {}

    public function getCurrentPrice(string $ticker): ?CurrentSecurityPriceData
    {
        $normalizedTicker = trim($ticker);

        if ($normalizedTicker === '') {
            return null;
        }

        $apiKey = $this->applicationConfig->getFinnhubApiKey();

        if ($apiKey === null) {
            return null;
        }

        $quoteResponse = Http::acceptJson()
            ->retry(2, 200)
            ->timeout(10)
            ->get(self::QUOTE_URL, [
                'symbol' => $normalizedTicker,
                'token' => $apiKey,
            ]);

        print_r($quoteResponse->body());

        if (! $quoteResponse->successful()) {
            return null;
        }

        /** @var array<string, mixed> $quotePayload */
        $quotePayload = $quoteResponse->json();
        $price = data_get($quotePayload, 'c');
        $quotedAtUnix = data_get($quotePayload, 't');

        if (! is_numeric($price)) {
            return null;
        }

        $priceFloat = (float) $price;

        if ($priceFloat === 0.0 && (is_numeric($quotedAtUnix) ? (int) $quotedAtUnix === 0 : true)) {
            return null;
        }

        $currency = $this->fetchProfileCurrency($normalizedTicker, $apiKey);

        if ($currency === null) {
            return null;
        }

        if (is_numeric($quotedAtUnix) && (int) $quotedAtUnix > 0) {
            $quotedAt = CarbonImmutable::createFromTimestampUTC((int) $quotedAtUnix);
        } else {
            $quotedAt = CarbonImmutable::now('UTC');
        }

        return new CurrentSecurityPriceData(
            ticker: $normalizedTicker,
            price: number_format($priceFloat, 10, '.', ''),
            currency: strtoupper($currency),
            quotedAt: $quotedAt,
            providerCode: SecurityDataProviderCode::Finnhub,
        );
    }

    private function fetchProfileCurrency(string $normalizedTicker, string $apiKey): ?string
    {
        $profileResponse = Http::acceptJson()
            ->retry(2, 200)
            ->timeout(10)
            ->get(self::PROFILE_URL, [
                'symbol' => $normalizedTicker,
                'token' => $apiKey,
            ]);

        if (! $profileResponse->successful()) {
            return null;
        }

        /** @var array<string, mixed> $profilePayload */
        $profilePayload = $profileResponse->json();
        $currency = data_get($profilePayload, 'currency');

        if (! is_string($currency) || $currency === '') {
            return null;
        }

        return $currency;
    }
}
