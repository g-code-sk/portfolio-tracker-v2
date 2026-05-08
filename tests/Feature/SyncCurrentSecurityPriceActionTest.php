<?php

namespace Tests\Feature;

use App\Models\Security;
use App\Models\SecurityDataProvider;
use Domain\Security\Action\SyncCurrentSecurityPriceAction;
use Domain\Security\Enums\SecurityDataProviderCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncCurrentSecurityPriceActionTest extends TestCase
{
    use RefreshDatabase;

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

        Http::fake([
            'finnhub.io/api/v1/quote*' => Http::response([
                'c' => 190.10,
                't' => 1710000000,
            ]),
            'finnhub.io/api/v1/stock/profile2*' => Http::response([
                'currency' => 'USD',
            ]),
        ]);

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

        Http::fake([
            'finnhub.io/api/v1/quote*' => Http::response([
                'c' => 0,
                't' => 0,
            ]),
        ]);

        $result = app(SyncCurrentSecurityPriceAction::class)->execute($security);

        $this->assertFalse($result->isUpdated);
        $this->assertTrue($result->isSkipped);
        $this->assertFalse($result->hasFailed);
    }
}
