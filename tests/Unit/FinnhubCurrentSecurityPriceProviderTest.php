<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Domain\Security\Data\CurrentSecurityPriceLookupInputData;
use Domain\Security\Enums\SecurityDataProviderCode;
use Domain\Security\Service\FinnhubCurrentSecurityPriceProvider;
use Finnhub\Api\DefaultApi;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FinnhubCurrentSecurityPriceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

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
        $this->mockFinnhubClient(function (MockInterface $client): void {
            $client->shouldReceive('quote')
                ->once()
                ->with('AAPL')
                ->andReturn([
                    'c' => 190.55,
                    'd' => 1.2,
                    'dp' => 0.63,
                    'h' => 191.0,
                    'l' => 188.5,
                    'o' => 189.0,
                    'pc' => 189.35,
                    't' => 1710000000,
                ]);

            $client->shouldReceive('companyProfile2')
                ->once()
                ->with('AAPL')
                ->andReturn([
                    'ticker' => 'AAPL',
                    'currency' => 'usd',
                    'exchange' => 'US',
                ]);
        });

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);
        $result = $provider->fetchCurrentPriceData($this->lookup('AAPL'));

        $this->assertNotNull($result);
        $this->assertSame('AAPL', $result->ticker);
        $this->assertSame('190.5500000000', $result->price);
        $this->assertSame('USD', $result->currency);
        $this->assertSame(SecurityDataProviderCode::Finnhub, $result->providerCode);
        $this->assertTrue($result->quotedAt->equalTo(CarbonImmutable::createFromTimestampUTC(1710000000)));
    }

    public function test_it_returns_null_when_profile_currency_is_missing(): void
    {
        $this->mockFinnhubClient(function (MockInterface $client): void {
            $client->shouldReceive('quote')
                ->once()
                ->with('FOO')
                ->andReturn([
                    'c' => 10.0,
                    't' => 1710000000,
                ]);

            $client->shouldReceive('companyProfile2')
                ->once()
                ->with('FOO')
                ->andReturn([
                    'ticker' => 'FOO',
                ]);
        });

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);

        $this->assertNull($provider->fetchCurrentPriceData($this->lookup('FOO')));
    }

    public function test_it_returns_null_when_quote_has_no_trade_data(): void
    {
        $this->mockFinnhubClient(function (MockInterface $client): void {
            $client->shouldReceive('quote')
                ->once()
                ->with('UNKNOWN')
                ->andReturn([
                    'c' => 0,
                    't' => 0,
                ]);

            $client->shouldReceive('symbolSearch')
                ->once()
                ->with('UNKNOWN')
                ->andReturn([
                    'result' => [],
                ]);
        });

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);

        $this->assertNull($provider->fetchCurrentPriceData($this->lookup('UNKNOWN')));
    }

    public function test_it_resolves_canonical_symbol_via_search_when_first_quote_is_empty(): void
    {
        $this->mockFinnhubClient(function (MockInterface $client): void {
            $client->shouldReceive('quote')
                ->once()
                ->with('FL')
                ->andReturn([
                    'c' => 0,
                    't' => 0,
                ]);

            $client->shouldReceive('symbolSearch')
                ->once()
                ->with('US3448491049')
                ->andReturn([
                    'result' => [
                        [
                            'symbol' => 'NYSE:FL',
                            'displaySymbol' => 'FL',
                            'description' => 'Foot Locker Inc',
                            'type' => 'Common Stock',
                            'isin' => 'US3448491049',
                        ],
                    ],
                ]);

            $client->shouldReceive('quote')
                ->once()
                ->with('NYSE:FL')
                ->andReturn([
                    'c' => 22.50,
                    't' => 1710000500,
                ]);

            $client->shouldReceive('companyProfile2')
                ->once()
                ->with('NYSE:FL')
                ->andReturn([
                    'currency' => 'usd',
                ]);
        });

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);
        $result = $provider->fetchCurrentPriceData($this->lookup('FL', 'Foot Locker Inc.', 'US3448491049'));

        $this->assertNotNull($result);
        $this->assertSame('FL', $result->ticker);
        $this->assertSame('22.5000000000', $result->price);
        $this->assertSame('USD', $result->currency);
        $this->assertTrue($result->quotedAt->equalTo(CarbonImmutable::createFromTimestampUTC(1710000500)));
    }

    public function test_it_returns_null_when_search_yields_multiple_canonical_symbols_for_same_ticker(): void
    {
        $this->mockFinnhubClient(function (MockInterface $client): void {
            $client->shouldReceive('quote')
                ->once()
                ->with('FL')
                ->andReturn([
                    'c' => 0,
                    't' => 0,
                ]);

            $client->shouldReceive('symbolSearch')
                ->once()
                ->with('FL')
                ->andReturn([
                    'result' => [
                        [
                            'symbol' => 'NYSE:FL',
                            'displaySymbol' => 'FL',
                            'description' => 'Foot Locker Inc',
                            'type' => 'Common Stock',
                        ],
                        [
                            'symbol' => 'TSX:FL',
                            'displaySymbol' => 'FL',
                            'description' => 'Florida Mining Corp',
                            'type' => 'Common Stock',
                        ],
                    ],
                ]);
        });

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);

        $this->assertNull($provider->fetchCurrentPriceData($this->lookup('FL')));
    }

    public function test_it_keeps_only_search_hits_matching_isin_when_isin_is_provided(): void
    {
        $this->mockFinnhubClient(function (MockInterface $client): void {
            $client->shouldReceive('quote')
                ->once()
                ->with('FL')
                ->andReturn([
                    'c' => 0,
                    't' => 0,
                ]);

            $client->shouldReceive('symbolSearch')
                ->once()
                ->with('US3448491049')
                ->andReturn([
                    'result' => [
                        [
                            'symbol' => 'NYSE:FL',
                            'displaySymbol' => 'FL',
                            'description' => 'Wrong Co',
                            'type' => 'Common Stock',
                            'isin' => 'US1111111111',
                        ],
                        [
                            'symbol' => 'TSX:FL',
                            'displaySymbol' => 'FL',
                            'description' => 'Foot Locker Inc',
                            'type' => 'Common Stock',
                            'isin' => 'US3448491049',
                        ],
                    ],
                ]);

            $client->shouldReceive('quote')
                ->once()
                ->with('TSX:FL')
                ->andReturn([
                    'c' => 30.00,
                    't' => 1710000600,
                ]);

            $client->shouldReceive('companyProfile2')
                ->once()
                ->with('TSX:FL')
                ->andReturn([
                    'currency' => 'CAD',
                ]);
        });

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);
        $result = $provider->fetchCurrentPriceData($this->lookup('FL', 'Foot Locker Inc.', 'US3448491049'));

        $this->assertNotNull($result);
        $this->assertSame('FL', $result->ticker);
        $this->assertSame('30.0000000000', $result->price);
        $this->assertSame('CAD', $result->currency);
    }

    public function test_it_filters_ambiguous_search_hits_using_company_name_tokens(): void
    {
        $this->mockFinnhubClient(function (MockInterface $client): void {
            $client->shouldReceive('quote')
                ->once()
                ->with('FL')
                ->andReturn([
                    'c' => 0,
                    't' => 0,
                ]);

            $client->shouldReceive('symbolSearch')
                ->once()
                ->with('Foot Locker Inc.')
                ->andReturn([
                    'result' => [
                        [
                            'symbol' => 'NYSE:FL',
                            'displaySymbol' => 'FL',
                            'description' => 'Foot Locker Inc',
                            'type' => 'Common Stock',
                        ],
                        [
                            'symbol' => 'TSX:FL',
                            'displaySymbol' => 'FL',
                            'description' => 'Zeta Mining PLC',
                            'type' => 'Common Stock',
                        ],
                    ],
                ]);

            $client->shouldReceive('quote')
                ->once()
                ->with('NYSE:FL')
                ->andReturn([
                    'c' => 18.25,
                    't' => 1710000700,
                ]);

            $client->shouldReceive('companyProfile2')
                ->once()
                ->with('NYSE:FL')
                ->andReturn([
                    'currency' => 'USD',
                ]);
        });

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);
        $result = $provider->fetchCurrentPriceData($this->lookup('FL', 'Foot Locker Inc.'));

        $this->assertNotNull($result);
        $this->assertSame('FL', $result->ticker);
        $this->assertSame('18.2500000000', $result->price);
    }

    public function test_it_returns_null_when_api_key_is_missing(): void
    {
        config(['services.finnhub.key' => '']);

        $this->mockFinnhubClient(function (MockInterface $client): void {
            $client->shouldNotReceive('quote');
            $client->shouldNotReceive('symbolSearch');
            $client->shouldNotReceive('companyProfile2');
        });

        $provider = $this->app->make(FinnhubCurrentSecurityPriceProvider::class);

        $this->assertNull($provider->fetchCurrentPriceData($this->lookup('AAPL')));
    }

    private function lookup(string $ticker, ?string $name = null, ?string $isin = null): CurrentSecurityPriceLookupInputData
    {
        $dto = CurrentSecurityPriceLookupInputData::tryFrom($ticker, $name, $isin);
        $this->assertNotNull($dto);

        return $dto;
    }

    /**
     * @param  callable(MockInterface): void  $expectations
     */
    private function mockFinnhubClient(callable $expectations): void
    {
        $client = Mockery::mock(DefaultApi::class);
        $expectations($client);

        $this->app->instance(DefaultApi::class, $client);
    }
}
