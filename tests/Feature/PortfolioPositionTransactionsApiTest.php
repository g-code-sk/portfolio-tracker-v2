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

class PortfolioPositionTransactionsApiTest extends TestCase
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

    public function test_it_returns_transactions_for_selected_position_and_currency(): void
    {
        $user = $this->createUser('owner@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create(['ticker' => 'AAPL', 'name' => 'Apple Inc.', 'isin' => 'US0378331005']);
        $otherSecurity = Security::query()->create(['ticker' => 'MSFT', 'name' => 'Microsoft', 'isin' => 'US5949181045']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);
        $eur = Currency::query()->create(['name' => 'Euro', 'symbol' => 'EUR']);
        $buyType = TransactionType::query()->where('code', TransactionTypeCode::Buy->value)->firstOrFail();

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'aapl-usd-1',
            'number_of_shares' => 2.0,
            'price_per_share' => 100.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-01-31',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'aapl-eur-1',
            'number_of_shares' => 1.0,
            'price_per_share' => 90.0,
            'currency_id' => $eur->id,
            'executed_at' => '2022-02-01',
        ]);

        Transaction::query()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $otherSecurity->id,
            'type_id' => $buyType->id,
            'external_transaction_id' => 'msft-usd-1',
            'number_of_shares' => 1.0,
            'price_per_share' => 50.0,
            'currency_id' => $usd->id,
            'executed_at' => '2022-02-02',
        ]);

        $response = $this->actingAsSpaUser($user)->get(
            "/api/portfolios/{$portfolio->id}/transactions?securityId={$security->id}&currencyId={$usd->id}"
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Portfolio transactions fetched successfully.')
            ->assertJsonCount(1, 'data.transactions')
            ->assertJsonPath('data.transactions.0.externalTransactionId', 'aapl-usd-1')
            ->assertJsonPath('data.transactions.0.currencySymbol', 'USD')
            ->assertJsonPath('data.transactions.0.ticker', 'AAPL')
            ->assertJsonPath('data.transactions.0.executedAt', '2022-01-31')
            ->assertJsonMissingPath('data.transactions.0.createdAt');
    }

    public function test_it_returns_empty_transactions_when_no_matching_rows_exist(): void
    {
        $user = $this->createUser('empty@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $user->id]);
        $security = Security::query()->create(['ticker' => 'AAPL', 'name' => 'Apple Inc.', 'isin' => 'US0378331005']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);

        $response = $this->actingAsSpaUser($user)->get(
            "/api/portfolios/{$portfolio->id}/transactions?securityId={$security->id}&currencyId={$usd->id}"
        );

        $response->assertOk()
            ->assertJsonCount(0, 'data.transactions');
    }

    public function test_it_rejects_access_for_non_owner_user(): void
    {
        $owner = $this->createUser('owner2@example.com');
        $intruder = $this->createUser('intruder@example.com');
        $portfolio = Portfolio::query()->create(['name' => 'Main', 'user_id' => $owner->id]);
        $security = Security::query()->create(['ticker' => 'AAPL', 'name' => 'Apple Inc.', 'isin' => 'US0378331005']);
        $usd = Currency::query()->create(['name' => 'US Dollar', 'symbol' => 'USD']);

        $response = $this->actingAsSpaUser($intruder)->get(
            "/api/portfolios/{$portfolio->id}/transactions?securityId={$security->id}&currencyId={$usd->id}"
        );

        $response->assertForbidden();
    }
}
