<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Domain\Security\Enums\SecurityDataProviderCode;
use Domain\Security\Service\FinnhubCurrentSecurityPriceProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinnhubCurrentSecurityPriceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.finnhub.key' => 'test-token',
            'services.SECURITY_DATA_PROVIDER' => 'finnhub',
        ]);
    }

    public function test_it_maps_quote_and_profile_payloads_to_current_price_data(): void
    {
        Http::fake([
            'finnhub.io/api/v1/quote*' => Http::response([
                'c' => 190.55,
                'd' => 1.2,
                'dp' => 0.63,
                'h' => 191.0,
                'l' => 188.5,
                'o' => 189.0,
                'pc' => 189.35,
                't' => 1710000000,
            ]),
            'finnhub.io/api/v1/stock/profile2*' => Http::response([
                'ticker' => 'AAPL',
                'currency' => 'usd',
                'exchange' => 'US',
            ]),
        ]);

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);
        $result = $provider->getCurrentPrice('AAPL');

        $this->assertNotNull($result);
        $this->assertSame('AAPL', $result->ticker);
        $this->assertSame('190.5500000000', $result->price);
        $this->assertSame('USD', $result->currency);
        $this->assertSame(SecurityDataProviderCode::Finnhub, $result->providerCode);
        $this->assertTrue($result->quotedAt->equalTo(CarbonImmutable::createFromTimestampUTC(1710000000)));
    }

    public function test_it_returns_null_when_profile_currency_is_missing(): void
    {
        Http::fake([
            'finnhub.io/api/v1/quote*' => Http::response([
                'c' => 10.0,
                't' => 1710000000,
            ]),
            'finnhub.io/api/v1/stock/profile2*' => Http::response([
                'ticker' => 'FOO',
            ]),
        ]);

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);

        $this->assertNull($provider->getCurrentPrice('FOO'));
    }

    public function test_it_returns_null_when_quote_has_no_trade_data(): void
    {
        Http::fake([
            'finnhub.io/api/v1/quote*' => Http::response([
                'c' => 0,
                't' => 0,
            ]),
        ]);

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);

        $this->assertNull($provider->getCurrentPrice('UNKNOWN'));
    }

    public function test_it_returns_null_when_api_key_is_missing(): void
    {
        config(['services.finnhub.key' => '']);

        Http::fake();

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);

        $this->assertNull($provider->getCurrentPrice('AAPL'));
        Http::assertNothingSent();
    }
}
