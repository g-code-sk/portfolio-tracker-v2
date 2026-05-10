<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Domain\Security\Data\SecuritySplitEventData;
use Domain\Security\Service\YahooSplitHistoryProvider;
use Mockery;
use Mockery\MockInterface;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\Exception\ApiException;
use Scheb\YahooFinanceApi\Results\SplitData;
use Tests\TestCase;

class YahooSplitHistoryProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_returns_split_event_dtos(): void
    {
        $start = CarbonImmutable::parse('2019-01-01')->utc()->startOfDay();
        $end = CarbonImmutable::parse('2025-01-01')->utc()->startOfDay();

        $this->mockYahooFinanceClient(function (MockInterface $client): void {
            $client->shouldReceive('getHistoricalSplitData')
                ->once()
                ->with('AAPL', Mockery::type(\DateTimeInterface::class), Mockery::type(\DateTimeInterface::class))
                ->andReturn([
                    new SplitData(new \DateTime('2020-08-31'), '4:1'),
                ]);
        });

        $provider = $this->app->make(YahooSplitHistoryProvider::class);
        $result = $provider->fetchSplitEvents('AAPL', $start, $end);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(SecuritySplitEventData::class, $result[0]);
        $this->assertSame('4:1', $result[0]->rawRatio);
    }

    public function test_it_returns_null_when_api_throws(): void
    {
        $start = CarbonImmutable::parse('2019-01-01')->utc()->startOfDay();
        $end = CarbonImmutable::parse('2025-01-01')->utc()->startOfDay();

        $this->mockYahooFinanceClient(function (MockInterface $client): void {
            $client->shouldReceive('getHistoricalSplitData')
                ->once()
                ->with('AAPL', Mockery::type(\DateTimeInterface::class), Mockery::type(\DateTimeInterface::class))
                ->andThrow(new ApiException('failed'));
        });

        $provider = $this->app->make(YahooSplitHistoryProvider::class);
        $result = $provider->fetchSplitEvents('AAPL', $start, $end);

        $this->assertNull($result);
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
