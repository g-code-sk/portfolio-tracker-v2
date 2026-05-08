<?php

namespace Tests\Feature;

use App\Models\Security;
use App\Models\SecurityDataProvider;
use Domain\Security\Enums\SecurityDataProviderCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncCurrentSecurityPricesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_only_selected_tickers_and_prints_summary(): void
    {
        $provider = SecurityDataProvider::query()->create([
            'code' => SecurityDataProviderCode::Yahoo->value,
            'name' => 'Yahoo Finance',
        ]);

        $aapl = Security::query()->create([
            'ticker' => 'AAPL',
            'name' => 'Apple Inc.',
            'isin' => 'US0378331005',
        ]);

        $msft = Security::query()->create([
            'ticker' => 'MSFT',
            'name' => 'Microsoft Corp.',
            'isin' => 'US5949181045',
        ]);

        Http::fake([
            'query1.finance.yahoo.com/*' => function ($request) {
                $symbol = $request['symbols'];

                if ($symbol === 'AAPL') {
                    return Http::response([
                        'quoteResponse' => [
                            'result' => [
                                [
                                    'regularMarketPrice' => 200.00,
                                    'currency' => 'USD',
                                    'regularMarketTime' => 1710000000,
                                ],
                            ],
                        ],
                    ]);
                }

                return Http::response([
                    'quoteResponse' => [
                        'result' => [],
                    ],
                ]);
            },
        ]);

        $this->artisan('securities:sync-current-prices', [
            '--ticker' => ['AAPL', 'MSFT'],
        ])
            ->expectsOutput('Current security price sync completed.')
            ->expectsOutput('Total scanned: 2')
            ->expectsOutput('Updated: 1')
            ->expectsOutput('Skipped: 1')
            ->expectsOutput('Failed: 0')
            ->assertSuccessful();

        $aapl->refresh();
        $msft->refresh();

        $this->assertSame('200.0000000000', $aapl->current_price);
        $this->assertSame('USD', $aapl->current_price_currency);
        $this->assertSame($provider->id, $aapl->current_data_provider_id);
        $this->assertNull($msft->current_price);
    }
}
