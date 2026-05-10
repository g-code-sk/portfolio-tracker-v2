<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Portfolio;
use App\Models\Security;
use App\Models\SecurityDataProvider;
use App\Models\SecuritySplit;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use Database\Seeders\TransactionTypeSeeder;
use Domain\Security\Enums\SecurityDataProviderCode;
use Domain\Transaction\Enums\TransactionTypeCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\MockInterface;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\Results\SplitData;
use Tests\TestCase;

class SyncSecuritySplitsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_persists_splits_for_tickers_with_transactions(): void
    {
        $this->seed(TransactionTypeSeeder::class);

        $yahooProvider = SecurityDataProvider::query()->create([
            'code' => SecurityDataProviderCode::Yahoo->value,
            'name' => 'Yahoo Finance',
        ]);

        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret1234'),
        ]);

        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create([
            'ticker' => 'AAPL',
            'name' => 'Apple Inc.',
            'isin' => 'US0378331005',
        ]);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);
        $buyType = TransactionType::query()->where('code', TransactionTypeCode::Buy->value)->firstOrFail();

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'aapl-usd-1',
            'number_of_shares' => 1.0,
            'price_per_share' => 100.0,
            'currency_id' => $usd->id,
            'executed_at' => '2019-01-15',
        ]);

        $this->mockYahooFinanceClient(function (MockInterface $client): void {
            $client->shouldReceive('getHistoricalSplitData')
                ->once()
                ->with('AAPL', Mockery::type(\DateTimeInterface::class), Mockery::type(\DateTimeInterface::class))
                ->andReturn([
                    new SplitData(new \DateTime('2020-08-31'), '4:1'),
                ]);
        });

        $this->artisan('securities:sync-splits', [
            '--ticker' => ['AAPL'],
        ])
            ->expectsOutput('Security split sync completed.')
            ->expectsOutput('Total scanned: 1')
            ->expectsOutput('Updated: 1')
            ->expectsOutput('Skipped: 0')
            ->expectsOutput('Failed: 0')
            ->assertSuccessful();

        $row = SecuritySplit::query()->where('security_id', $security->id)->firstOrFail();
        $this->assertSame($yahooProvider->id, $row->security_data_provider_id);
        $this->assertSame('2020-08-31', $row->effective_on->toDateString());
        $this->assertSame(4, $row->ratio_numerator);
        $this->assertSame(1, $row->ratio_denominator);
        $this->assertSame('4:1', $row->raw_ratio);
    }

    public function test_it_skips_securities_without_transactions_and_performs_no_http_calls(): void
    {
        $this->seed(TransactionTypeSeeder::class);

        SecurityDataProvider::query()->create([
            'code' => SecurityDataProviderCode::Yahoo->value,
            'name' => 'Yahoo Finance',
        ]);

        Security::query()->create([
            'ticker' => 'MSFT',
            'name' => 'Microsoft Corp.',
            'isin' => 'US5949181045',
        ]);

        $this->mockYahooFinanceClient(function (MockInterface $client): void {
            $client->shouldNotReceive('getHistoricalSplitData');
        });

        $this->artisan('securities:sync-splits', [
            '--ticker' => ['MSFT'],
        ])
            ->expectsOutput('Total scanned: 1')
            ->expectsOutput('Updated: 0')
            ->expectsOutput('Skipped: 1')
            ->expectsOutput('Failed: 0')
            ->assertSuccessful();
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
