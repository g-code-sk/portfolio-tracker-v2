<?php

namespace Tests\Feature;

use App\Models\Security;
use App\Models\SecurityDataProvider;
use Domain\Security\Action\SyncCurrentSecurityPriceAction;
use Domain\Security\Enums\SecurityDataProviderCode;
use Finnhub\Api\DefaultApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SyncCurrentSecurityPriceActionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.SECURITY_DATA_PROVIDER' => 'finnhub',
            'services.finnhub.key' => 'test-finnhub-api-key',
        ]);
    }

    public function test_it_persists_current_price_fields_for_a_security(): void
    {
        $provider = SecurityDataProvider::query()->create([
            'code' => SecurityDataProviderCode::Finnhub->value,
            'name' => 'Finnhub',
        ]);

        $security = Security::query()->create([
            'ticker' => 'AAPL',
            'name' => 'Apple Inc.',
            'isin' => 'US0378331005',
        ]);

        $this->mockFinnhubClient(function (MockInterface $client): void {
            $client->shouldReceive('quote')
                ->once()
                ->with('AAPL')
                ->andReturn([
                'c' => 190.10,
                't' => 1710000000,
            ]);

            $client->shouldReceive('companyProfile2')
                ->once()
                ->with('AAPL')
                ->andReturn([
                'currency' => 'USD',
            ]);
        });

        $result = app(SyncCurrentSecurityPriceAction::class)->execute($security);

        $this->assertTrue($result->isUpdated);
        $this->assertFalse($result->isSkipped);
        $this->assertFalse($result->hasFailed);

        $security->refresh();

        $this->assertSame('190.1000000000', $security->current_price);
        $this->assertSame('USD', $security->current_price_currency);
        $this->assertNotNull($security->current_price_updated_at);
        $this->assertSame($provider->id, $security->current_data_provider_id);
    }

    public function test_it_skips_when_provider_cannot_return_a_quote(): void
    {
        $security = Security::query()->create([
            'ticker' => 'UNKNOWN',
            'name' => 'Unknown Inc.',
        ]);

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
                ->with('Unknown Inc.')
                ->andReturn([
                'result' => [],
            ]);

            $client->shouldReceive('symbolSearch')
                ->once()
                ->with('UNKNOWN')
                ->andReturn([
                'result' => [],
            ]);
        });

        $result = app(SyncCurrentSecurityPriceAction::class)->execute($security);

        $this->assertFalse($result->isUpdated);
        $this->assertTrue($result->isSkipped);
        $this->assertFalse($result->hasFailed);
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
