<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Portfolio;
use App\Models\Security;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\TransactionTypeSeeder;
use Domain\Transaction\Enums\TransactionTypeCode;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortfolioWholeShareBuySegmentsApiTest extends TestCase
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

    public function test_it_returns_aligned_buy_and_sell_groups_with_days_to_sell(): void
    {
        $user = $this->createUser('owner@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create(['ticker' => 'XY', 'name' => 'XY Corp', 'isin' => 'US0000000000']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);
        $buyType = TransactionType::query()->where('code', TransactionTypeCode::Buy->value)->firstOrFail();
        $sellType = TransactionType::query()->where('code', TransactionTypeCode::Sell->value)->firstOrFail();

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'tx-1',
            'number_of_shares' => 0.5,
            'price_per_share' => 10.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-01',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'tx-2',
            'number_of_shares' => 0.5,
            'price_per_share' => 14.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-02',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'tx-3',
            'number_of_shares' => 1.0,
            'price_per_share' => 10.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-05',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $sellType->id,
            'external_transaction_id' => 'sell-1',
            'number_of_shares' => 0.5,
            'price_per_share' => 16.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-10',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $sellType->id,
            'external_transaction_id' => 'sell-2',
            'number_of_shares' => 0.5,
            'price_per_share' => 20.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-11',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $sellType->id,
            'external_transaction_id' => 'sell-3',
            'number_of_shares' => 1.0,
            'price_per_share' => 15.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-20',
        ]);

        $response = $this->actingAsSpaUser($user)->get(
            "/api/portfolios/{$portfolio->id}/transactions/whole-share-buy-segments?securityId={$security->id}&currencyId={$usd->id}"
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Whole share segments fetched successfully.')
            ->assertJsonCount(2, 'data.groups')
            ->assertJsonPath('data.groups.0.groupIndex', 0)
            ->assertJsonPath('data.groups.1.groupIndex', 1)
            ->assertJsonPath('data.groups.0.buyDate', '2022-01-02')
            ->assertJsonPath('data.groups.0.sellDate', '2022-01-10')
            ->assertJsonPath('data.groups.0.holdPeriodDays', 8)
            ->assertJsonPath('data.groups.1.buyDate', '2022-01-05')
            ->assertJsonPath('data.groups.1.sellDate', '2022-01-20')
            ->assertJsonPath('data.groups.1.holdPeriodDays', 15)
            ->assertJsonPath('data.groups.0.weightedBuyPricePerShare', 12)
            ->assertJsonPath('data.groups.0.weightedSellPricePerShare', 18)
            ->assertJsonPath('data.groups.0.yieldPercent', 50)
            ->assertJsonPath('data.groups.0.yieldAmount', 6)
            ->assertJsonPath('data.groups.0.isSellTaxable', true)
            ->assertJsonPath('data.groups.1.weightedBuyPricePerShare', 10)
            ->assertJsonPath('data.groups.1.weightedSellPricePerShare', 15)
            ->assertJsonPath('data.groups.1.yieldPercent', 50)
            ->assertJsonPath('data.groups.1.yieldAmount', 5)
            ->assertJsonPath('data.groups.1.isSellTaxable', true)
            ->assertJsonCount(2, 'data.groups.0.buyBucket.segments')
            ->assertJsonCount(2, 'data.groups.0.sellBucket.segments')
            ->assertJsonCount(1, 'data.groups.1.buyBucket.segments')
            ->assertJsonCount(1, 'data.groups.1.sellBucket.segments');
    }

    public function test_it_returns_sell_only_group_rows_when_no_buys_exist(): void
    {
        $user = $this->createUser('seller@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create(['ticker' => 'ABC', 'name' => 'ABC Inc', 'isin' => 'US1111111111']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);
        $sellType = TransactionType::query()->where('code', TransactionTypeCode::Sell->value)->firstOrFail();

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $sellType->id,
            'external_transaction_id' => 'sell-1',
            'number_of_shares' => 1.0,
            'price_per_share' => 50.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-06-01',
        ]);

        $response = $this->actingAsSpaUser($user)->get(
            "/api/portfolios/{$portfolio->id}/transactions/whole-share-buy-segments?securityId={$security->id}&currencyId={$usd->id}"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data.groups')
            ->assertJsonPath('data.groups.0.groupIndex', 0)
            ->assertJsonPath('data.groups.0.buyBucket', null)
            ->assertJsonPath('data.groups.0.sellDate', '2022-06-01')
            ->assertJsonPath('data.groups.0.buyDate', null)
            ->assertJsonPath('data.groups.0.holdPeriodDays', null)
            ->assertJsonPath('data.groups.0.weightedBuyPricePerShare', null)
            ->assertJsonPath('data.groups.0.weightedSellPricePerShare', 50)
            ->assertJsonPath('data.groups.0.yieldPercent', null)
            ->assertJsonPath('data.groups.0.yieldAmount', null)
            ->assertJsonPath('data.groups.0.isSellTaxable', null);
    }

    public function test_it_uses_current_date_for_taxability_when_sell_is_missing(): void
    {
        $user = $this->createUser('holder@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create(['ticker' => 'LONG', 'name' => 'Long Corp', 'isin' => 'US4444444444']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);
        $buyType = TransactionType::query()->where('code', TransactionTypeCode::Buy->value)->firstOrFail();

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'buy-1',
            'number_of_shares' => 1.0,
            'price_per_share' => 20.0,
            'currency_id' => $usd->id,
            'executed_at' => '2024-01-10',
        ]);

        try {
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-01-10'));

            $beforeThresholdResponse = $this->actingAsSpaUser($user)->get(
                "/api/portfolios/{$portfolio->id}/transactions/whole-share-buy-segments?securityId={$security->id}&currencyId={$usd->id}"
            );

            $beforeThresholdResponse->assertOk()
                ->assertJsonPath('data.groups.0.buyDate', '2024-01-10')
                ->assertJsonPath('data.groups.0.sellDate', null)
                ->assertJsonPath('data.groups.0.holdPeriodDays', null)
                ->assertJsonPath('data.groups.0.isSellTaxable', true);

            CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-01-11'));

            $onThresholdResponse = $this->actingAsSpaUser($user)->get(
                "/api/portfolios/{$portfolio->id}/transactions/whole-share-buy-segments?securityId={$security->id}&currencyId={$usd->id}"
            );

            $onThresholdResponse->assertOk()
                ->assertJsonPath('data.groups.0.buyDate', '2024-01-10')
                ->assertJsonPath('data.groups.0.sellDate', null)
                ->assertJsonPath('data.groups.0.holdPeriodDays', null)
                ->assertJsonPath('data.groups.0.isSellTaxable', false);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_it_validates_security_id_and_currency_id_as_required(): void
    {
        $user = $this->createUser('val@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create(['ticker' => 'ABC', 'name' => 'ABC Inc', 'isin' => 'US2222222222']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);

        $response = $this->actingAsSpaUser($user)->get(
            "/api/portfolios/{$portfolio->id}/transactions/whole-share-buy-segments?securityId={$security->id}"
        );

        $response->assertUnprocessable();

        $responseB = $this->actingAsSpaUser($user)->get(
            "/api/portfolios/{$portfolio->id}/transactions/whole-share-buy-segments?currencyId={$usd->id}"
        );

        $responseB->assertUnprocessable();
    }

    public function test_it_rejects_access_for_non_owner_user(): void
    {
        $owner = $this->createUser('owner99@example.com');
        $intruder = $this->createUser('intruder99@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $owner->id]);
        $security = Security::query()->create(['ticker' => 'ABC', 'name' => 'ABC Inc', 'isin' => 'US3333333333']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);

        $response = $this->actingAsSpaUser($intruder)->get(
            "/api/portfolios/{$portfolio->id}/transactions/whole-share-buy-segments?securityId={$security->id}&currencyId={$usd->id}"
        );

        $response->assertForbidden();
    }

    public function test_it_does_not_mix_closed_cycle_buys_with_reopened_position_buys(): void
    {
        $user = $this->createUser('reopen@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create(['ticker' => 'GOOGL', 'name' => 'Alphabet Inc', 'isin' => 'US02079K3059']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);
        $buyType = TransactionType::query()->where('code', TransactionTypeCode::Buy->value)->firstOrFail();
        $sellType = TransactionType::query()->where('code', TransactionTypeCode::Sell->value)->firstOrFail();

        // Cycle 1: buy 1.0 in 2022, sell 1.0 in 2024.
        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'buy-2022-a',
            'number_of_shares' => 0.87,
            'price_per_share' => 114.55,
            'currency_id' => $usd->id,
            'executed_at' => '2022-08-23',
        ]);
        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'buy-2022-b',
            'number_of_shares' => 0.13,
            'price_per_share' => 162.25,
            'currency_id' => $usd->id,
            'executed_at' => '2022-09-01',
        ]);
        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $sellType->id,
            'external_transaction_id' => 'sell-2024-a',
            'number_of_shares' => 1.0,
            'price_per_share' => 140.87,
            'currency_id' => $usd->id,
            'executed_at' => '2024-02-16',
        ]);

        // Cycle 2: position reopened in 2025 (still open).
        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'buy-2025-a',
            'number_of_shares' => 1.0,
            'price_per_share' => 162.25,
            'currency_id' => $usd->id,
            'executed_at' => '2025-04-25',
        ]);

        try {
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-05-06'));

            $response = $this->actingAsSpaUser($user)->get(
                "/api/portfolios/{$portfolio->id}/transactions/whole-share-buy-segments?securityId={$security->id}&currencyId={$usd->id}"
            );

            $response->assertOk()
                ->assertJsonCount(2, 'data.groups')
                ->assertJsonPath('data.groups.0.groupIndex', 0)
                ->assertJsonPath('data.groups.0.buyDate', '2022-09-01')
                ->assertJsonPath('data.groups.0.sellDate', '2024-02-16')
                ->assertJsonPath('data.groups.0.holdPeriodDays', 533)
                ->assertJsonPath('data.groups.0.isSellTaxable', false)
                ->assertJsonPath('data.groups.1.groupIndex', 1)
                ->assertJsonPath('data.groups.1.buyDate', '2025-04-25')
                ->assertJsonPath('data.groups.1.sellDate', null)
                ->assertJsonPath('data.groups.1.holdPeriodDays', null)
                ->assertJsonPath('data.groups.1.weightedBuyPricePerShare', 162.25)
                ->assertJsonPath('data.groups.1.weightedSellPricePerShare', null)
                ->assertJsonPath('data.groups.1.yieldPercent', null)
                ->assertJsonPath('data.groups.1.yieldAmount', null);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
