<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Domain\Security\Enums\SecurityDataProviderCode;
use Domain\Security\Service\YahooCurrentSecurityPriceProvider;
use Mockery;
use Mockery\MockInterface;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\Results\Quote;
use Tests\TestCase;

class YahooCurrentSecurityPriceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_maps_quote_payload_to_current_price_data(): void
    {
        $this->mockYahooFinanceClient(function (MockInterface $client): void {
            $client->shouldReceive('getQuote')
                ->once()
                ->with('AAPL')
                ->andReturn(new Quote([
                    'regularMarketPrice' => 189.52,
                    'currency' => 'usd',
                    'regularMarketTime' => CarbonImmutable::createFromTimestampUTC(1710000000),
                ]));
        });

        $provider = $this->app->make(YahooCurrentSecurityPriceProvider::class);
        $result = $provider->fetchCurrentPriceData('AAPL');

        $this->assertNotNull($result);
        $this->assertSame('AAPL', $result->ticker);
        $this->assertSame('189.5200000000', $result->price);
        $this->assertSame('USD', $result->currency);
        $this->assertSame(SecurityDataProviderCode::Yahoo, $result->providerCode);
        $this->assertTrue($result->quotedAt->equalTo(CarbonImmutable::createFromTimestampUTC(1710000000)));
    }

    public function test_it_returns_null_when_quote_payload_is_missing_price(): void
    {
        $this->mockYahooFinanceClient(function (MockInterface $client): void {
            $client->shouldReceive('getQuote')
                ->once()
                ->with('AAPL')
                ->andReturn(new Quote([
                    'currency' => 'USD',
                ]));
        });

        $provider = $this->app->make(YahooCurrentSecurityPriceProvider::class);

        $this->assertNull($provider->fetchCurrentPriceData('AAPL'));
    }

    /**
     * @param  callable(MockInterface): void  $expectations
     */
    private function mockYahooFinanceClient(callable $expectations): void
    {
        $client = Mockery::mock(ApiClient::class);
        $expectations($client);

        $this->app->instance(ApiClient::class, $client);
    }
}
