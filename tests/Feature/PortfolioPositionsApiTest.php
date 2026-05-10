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
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortfolioPositionsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(TransactionTypeSeeder::class);
    }

    private function withSpaHeaders(): self
    {
        return $this
            ->withHeader('Accept', 'application/json')
            ->withHeader('Origin', 'http://localhost:5173')
            ->withHeader('Referer', 'http://localhost:5173/portfolios');
    }

    private function actingAsSpaUser(User $user): self
    {
        return $this->withSpaHeaders()->actingAs($user, 'web');
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('secret1234'),
        ]);
    }

    public function test_returns_empty_positions_when_portfolio_has_no_transactions(): void
    {
        $user = $this->createUser('empty@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);

        $response = $this->actingAsSpaUser($user)->get("/api/portfolios/{$portfolio->id}/positions");

        $response->assertOk()
            ->assertJsonPath('message', 'Portfolio positions fetched successfully.')
            ->assertJsonCount(0, 'data.positions');
    }

    public function test_positions_aggregate_raw_amounts_when_no_splits_exist(): void
    {
        $user = $this->createUser('owner@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $msft = Security::query()->create(['ticker' => 'MSFT', 'name' => 'Microsoft', 'isin' => 'US5949181045']);
        $aapl = Security::query()->create([
            'ticker' => 'AAPL',
            'name' => 'Apple Inc.',
            'isin' => 'US0378331005',
            'current_price' => 110.0,
            'current_price_currency' => 'USD',
        ]);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);
        $buyType = TransactionType::query()->where('code', TransactionTypeCode::Buy->value)->firstOrFail();
        $sellType = TransactionType::query()->where('code', TransactionTypeCode::Sell->value)->firstOrFail();

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $msft->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'msft-1',
            'number_of_shares' => 1.0,
            'price_per_share' => 300.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-01',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $aapl->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'aapl-buy',
            'number_of_shares' => 10.0,
            'price_per_share' => 100.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-02',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $aapl->id,
            'type_id' => $sellType->id,
            'external_transaction_id' => 'aapl-sell',
            'number_of_shares' => 5.0,
            'price_per_share' => 120.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-03',
        ]);

        $response = $this->actingAsSpaUser($user)->get("/api/portfolios/{$portfolio->id}/positions");

        $response->assertOk()->assertJsonCount(2, 'data.positions');

        $positions = $response->json('data.positions');
        $this->assertSame('AAPL', $positions[0]['ticker']);
        $this->assertEqualsWithDelta(10.0, $positions[0]['sharesBought'], 1e-9);
        $this->assertEqualsWithDelta(5.0, $positions[0]['sharesSold'], 1e-9);
        $this->assertEqualsWithDelta(1000.0, $positions[0]['investedAmount'], 1e-9);
        $this->assertEqualsWithDelta(600.0, $positions[0]['soldAmount'], 1e-9);
        $this->assertEqualsWithDelta(5.0, $positions[0]['totalShares'], 1e-9);
        $this->assertEqualsWithDelta(150.0, $positions[0]['totalGainLossAmount'], 1e-9);
        $this->assertSame('MSFT', $positions[1]['ticker']);
        $this->assertEqualsWithDelta(1.0, $positions[1]['totalShares'], 1e-9);
        $this->assertEqualsWithDelta(300.0, $positions[1]['investedAmount'], 1e-9);
    }

    public function test_positions_scale_share_totals_when_split_occurs_after_trade(): void
    {
        $yahooProvider = SecurityDataProvider::query()->create([
            'code' => SecurityDataProviderCode::Yahoo->value,
            'name' => 'Yahoo Finance',
        ]);

        $user = $this->createUser('split@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create([
            'ticker' => 'SPLT',
            'name' => 'SplitCo',
            'isin' => 'US0000000001',
        ]);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);
        $buyType = TransactionType::query()->where('code', TransactionTypeCode::Buy->value)->firstOrFail();

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'pre-split-buy',
            'number_of_shares' => 100.0,
            'price_per_share' => 40.0,
            'currency_id' => $usd->id,
            'executed_at' => '2020-06-01',
        ]);

        SecuritySplit::query()->create([
            'security_id' => $security->id,
            'security_data_provider_id' => $yahooProvider->id,
            'effective_on' => '2020-08-31',
            'ratio_numerator' => 4,
            'ratio_denominator' => 1,
            'raw_ratio' => '4:1',
        ]);

        $response = $this->actingAsSpaUser($user)->get("/api/portfolios/{$portfolio->id}/positions");

        $response->assertOk()->assertJsonCount(1, 'data.positions');

        $position = $response->json('data.positions.0');
        $this->assertEqualsWithDelta(400.0, $position['sharesBought'], 1e-9);
        $this->assertEqualsWithDelta(400.0, $position['totalShares'], 1e-9);
        $this->assertEqualsWithDelta(4000.0, $position['investedAmount'], 1e-9);
    }
}
