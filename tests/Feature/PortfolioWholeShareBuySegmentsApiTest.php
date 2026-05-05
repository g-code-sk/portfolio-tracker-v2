<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Portfolio;
use App\Models\Security;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
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

    public function test_it_splits_partial_buys_at_whole_share_boundaries(): void
    {
        $user = $this->createUser('owner@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create(['ticker' => 'XY', 'name' => 'XY Corp', 'isin' => 'US0000000000']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);
        $buyType = TransactionType::query()->where('code', TransactionTypeCode::Buy->value)->firstOrFail();

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
            'number_of_shares' => 0.3,
            'price_per_share' => 10.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-02',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'tx-3',
            'number_of_shares' => 0.5,
            'price_per_share' => 10.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-03',
        ]);

        $response = $this->actingAsSpaUser($user)->get(
            "/api/portfolios/{$portfolio->id}/transactions/whole-share-buy-segments?securityId={$security->id}&currencyId={$usd->id}"
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Whole share buy segments fetched successfully.')
            ->assertJsonCount(2, 'data.buckets')
            ->assertJsonPath('data.buckets.0.wholeShareBucketIndex', 0)
            ->assertJsonCount(3, 'data.buckets.0.segments')
            ->assertJsonPath('data.buckets.1.wholeShareBucketIndex', 1)
            ->assertJsonCount(1, 'data.buckets.1.segments');

        self::assertEqualsWithDelta(0.5, (float) $response->json('data.buckets.0.segments.0.numberOfShares'), 1e-6);
        self::assertEqualsWithDelta(0.3, (float) $response->json('data.buckets.0.segments.1.numberOfShares'), 1e-6);
        self::assertEqualsWithDelta(0.2, (float) $response->json('data.buckets.0.segments.2.numberOfShares'), 1e-6);
        self::assertEqualsWithDelta(0.3, (float) $response->json('data.buckets.1.segments.0.numberOfShares'), 1e-6);

        self::assertEqualsWithDelta(5.0, (float) $response->json('data.buckets.0.segments.0.totalAmount'), 1e-6);
        self::assertEqualsWithDelta(3.0, (float) $response->json('data.buckets.0.segments.1.totalAmount'), 1e-6);
        self::assertEqualsWithDelta(2.0, (float) $response->json('data.buckets.0.segments.2.totalAmount'), 1e-6);
        self::assertEqualsWithDelta(3.0, (float) $response->json('data.buckets.1.segments.0.totalAmount'), 1e-6);
    }

    public function test_it_returns_empty_buckets_when_only_sell_transactions_exist(): void
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
            ->assertJsonCount(0, 'data.buckets');
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
}
