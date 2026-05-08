<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Domain\Security\Enums\SecurityDataProviderCode;
use Domain\Security\Service\YahooCurrentSecurityPriceProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YahooCurrentSecurityPriceProviderTest extends TestCase
{
    public function test_it_maps_quote_payload_to_current_price_data(): void
    {
        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response([
                'quoteResponse' => [
                    'result' => [
                        [
                            'regularMarketPrice' => 189.52,
                            'currency' => 'usd',
                            'regularMarketTime' => 1710000000,
                        ],
                    ],
                ],
            ]),
        ]);

        $provider = new YahooCurrentSecurityPriceProvider;
        $result = $provider->getCurrentPrice('AAPL');

        $this->assertNotNull($result);
        $this->assertSame('AAPL', $result->ticker);
        $this->assertSame('189.5200000000', $result->price);
        $this->assertSame('USD', $result->currency);
        $this->assertSame(SecurityDataProviderCode::Yahoo, $result->providerCode);
        $this->assertTrue($result->quotedAt->equalTo(CarbonImmutable::createFromTimestampUTC(1710000000)));
    }

    public function test_it_returns_null_when_quote_payload_is_missing_price(): void
    {
        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response([
                'quoteResponse' => [
                    'result' => [
                        [
                            'currency' => 'USD',
                        ],
                    ],
                ],
            ]),
        ]);

        $provider = new YahooCurrentSecurityPriceProvider;

        $this->assertNull($provider->getCurrentPrice('AAPL'));
    }
}
