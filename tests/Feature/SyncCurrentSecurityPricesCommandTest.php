<?php

namespace Tests\Feature;

use App\Models\Security;
use App\Models\SecurityDataProvider;
use Domain\Security\Enums\SecurityDataProviderCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncCurrentSecurityPricesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.SECURITY_DATA_PROVIDER' => 'finnhub',
            'services.finnhub.key' => 'test-finnhub-api-key',
            'services.security_price_refresh_after_hours' => 24,
        ]);
    }

    public function test_it_syncs_only_selected_tickers_and_prints_summary(): void
    {
        $provider = SecurityDataProvider::query()->create([
            'code' => SecurityDataProviderCode::Finnhub->value,
            'name' => 'Finnhub',
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

        Http::fake(function ($request) {
            $uri = $request->url();

            if (str_contains($uri, '/api/v1/quote')) {
                $symbol = $request['symbol'] ?? '';

                if ($symbol === 'AAPL') {
                    return Http::response([
                        'c' => 200.00,
                        't' => 1710000000,
                    ]);
                }

                return Http::response([
                    'c' => 0,
                    't' => 0,
                ]);
            }

            if (str_contains($uri, '/stock/profile2')) {
                return Http::response([
                    'currency' => 'USD',
                ]);
            }

            return Http::response([], 404);
        });

        $this->artisan('securities:sync-current-prices', [
            '--ticker' => ['AAPL', 'MSFT'],
            '--force' => true,
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

    public function test_it_skips_recently_updated_security_without_force_and_performs_no_http_calls(): void
    {
        SecurityDataProvider::query()->create([
            'code' => SecurityDataProviderCode::Finnhub->value,
            'name' => 'Finnhub',
        ]);

        $security = Security::query()->create([
            'ticker' => 'AAPL',
            'name' => 'Apple Inc.',
            'isin' => 'US0378331005',
            'current_price' => '180.0000000000',
            'current_price_currency' => 'USD',
            'current_price_updated_at' => Carbon::now(),
        ]);

        Http::fake();

        $this->artisan('securities:sync-current-prices', [
            '--ticker' => ['AAPL'],
        ])
            ->expectsOutput('Total scanned: 1')
            ->expectsOutput('Updated: 0')
            ->expectsOutput('Skipped: 1')
            ->expectsOutput('Failed: 0')
            ->assertSuccessful();

        Http::assertNothingSent();

        $security->refresh();
        $this->assertSame('180.0000000000', $security->current_price);
    }

    public function test_it_refreshes_recently_updated_security_when_force_is_set(): void
    {
        SecurityDataProvider::query()->create([
            'code' => SecurityDataProviderCode::Finnhub->value,
            'name' => 'Finnhub',
        ]);

        $security = Security::query()->create([
            'ticker' => 'AAPL',
            'name' => 'Apple Inc.',
            'isin' => 'US0378331005',
            'current_price' => '180.0000000000',
            'current_price_currency' => 'USD',
            'current_price_updated_at' => Carbon::now(),
        ]);

        Http::fake(function ($request) {
            $uri = $request->url();

            if (str_contains($uri, '/api/v1/quote')) {
                return Http::response([
                    'c' => 199.99,
                    't' => 1710000000,
                ]);
            }

            if (str_contains($uri, '/stock/profile2')) {
                return Http::response([
                    'currency' => 'USD',
                ]);
            }

            return Http::response([], 404);
        });

        $this->artisan('securities:sync-current-prices', [
            '--ticker' => ['AAPL'],
            '--force' => true,
        ])
            ->expectsOutput('Total scanned: 1')
            ->expectsOutput('Updated: 1')
            ->expectsOutput('Skipped: 0')
            ->expectsOutput('Failed: 0')
            ->assertSuccessful();

        $security->refresh();
        $this->assertSame('199.9900000000', $security->current_price);
    }
}
