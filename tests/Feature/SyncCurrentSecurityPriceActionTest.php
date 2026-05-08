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

    public function test_it_persists_current_price_fields_for_a_security(): void
    {
        $provider = SecurityDataProvider::query()->create([
            'code' => SecurityDataProviderCode::Yahoo->value,
            'name' => 'Yahoo Finance',
        ]);

        $security = Security::query()->create([
            'ticker' => 'AAPL',
            'name' => 'Apple Inc.',
            'isin' => 'US0378331005',
        ]);

        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response([
                'quoteResponse' => [
                    'result' => [
                        [
                            'regularMarketPrice' => 190.10,
                            'currency' => 'USD',
                            'regularMarketTime' => 1710000000,
                        ],
                    ],
                ],
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
            'query1.finance.yahoo.com/*' => Http::response([
                'quoteResponse' => [
                    'result' => [],
                ],
            ]),
        ]);

        $result = app(SyncCurrentSecurityPriceAction::class)->execute($security);

        $this->assertFalse($result->isUpdated);
        $this->assertTrue($result->isSkipped);
        $this->assertFalse($result->hasFailed);
    }
}
