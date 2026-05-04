<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\SecurityTypeSeeder;
use Domain\Transaction\Enums\TransactionImportType;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortfolioTransactionImportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
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

    private function createUserWithPortfolio(): array
    {
        $user = User::query()->create([
            'name' => 'Import User',
            'email' => 'import@example.com',
            'password' => Hash::make('secret1234'),
        ]);

        $portfolio = Portfolio::query()->create([
            'name' => 'Import Portfolio',
            'user_id' => $user->id,
        ]);

        return [$user, $portfolio];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function trading212HeaderAndDepositRow(): array
    {
        $header = implode(',', [
            'Action',
            'Time',
            'ISIN',
            'Ticker',
            'Name',
            'Notes',
            'ID',
            'No. of shares',
            'Price / share',
            'Currency (Price / share)',
            'Exchange rate',
            'Result',
            'Currency (Result)',
            'Total',
            'Currency (Total)',
            'Withholding tax',
            'Currency (Withholding tax)',
            'Currency conversion fee',
            'Currency (Currency conversion fee)',
        ]);

        $depositRow = implode(',', [
            'Deposit',
            '2022-01-26 07:55:40',
            '',
            '',
            '',
            'Bank Transfer',
            '069abe0c-3825-488e-8af3-51304920acac',
            '',
            '',
            '',
            '',
            '',
            '',
            '500.00',
            'EUR',
            '',
            '',
            '',
            '',
        ]);

        return [$header, $depositRow];
    }

    public function test_non_trading212_import_does_not_persist_transactions(): void
    {
        $this->seed(SecurityTypeSeeder::class);

        [$user, $portfolio] = $this->createUserWithPortfolio();

        $csv = "not,a,valid,header\n1,2,3\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $response = $this->actingAsSpaUser($user)->post(
            "/api/portfolios/{$portfolio->id}/transactions/import",
            [
                'importType' => TransactionImportType::InteractiveBrokers->value,
                'file' => $file,
            ],
        );

        $response->assertCreated()
            ->assertJsonPath('message', 'Import file uploaded successfully.');

        $this->assertSame(0, Transaction::query()->where('portfolio_id', $portfolio->id)->count());
    }

    public function test_trading212_import_rejects_invalid_headers(): void
    {
        $this->seed(SecurityTypeSeeder::class);

        [$user, $portfolio] = $this->createUserWithPortfolio();

        $csv = "not,a,valid,header\n1,2,3\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $response = $this->actingAsSpaUser($user)->post(
            "/api/portfolios/{$portfolio->id}/transactions/import",
            [
                'importType' => TransactionImportType::Trading212->value,
                'file' => $file,
            ],
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['file']);

        $this->assertSame(0, Transaction::query()->where('portfolio_id', $portfolio->id)->count());
    }

    public function test_trading212_import_persists_market_buy_and_skips_deposit(): void
    {
        $this->seed(SecurityTypeSeeder::class);

        [$user, $portfolio] = $this->createUserWithPortfolio();

        [$header, $depositRow] = $this->trading212HeaderAndDepositRow();

        $buyRow = implode(',', [
            'Market buy',
            '2022-01-31 16:40:01',
            'US1234567890',
            'TEST',
            'Test Co',
            '',
            '11111111-1111-1111-1111-111111111111',
            '1.0000000000',
            '10.0000000000',
            'USD',
            '1.00000000',
            '',
            '',
            '10.00',
            'USD',
            '',
            '',
            '',
            '',
        ]);

        $csv = $header."\n".$depositRow."\n".$buyRow."\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $response = $this->actingAsSpaUser($user)->post(
            "/api/portfolios/{$portfolio->id}/transactions/import",
            [
                'importType' => TransactionImportType::Trading212->value,
                'file' => $file,
            ],
        );

        $response->assertCreated()
            ->assertJsonPath('message', 'Import file uploaded successfully.');

        $transactions = Transaction::query()->where('portfolio_id', $portfolio->id)->get();
        $this->assertCount(1, $transactions);

        $transaction = $transactions->first();
        $this->assertSame('11111111-1111-1111-1111-111111111111', $transaction->external_transaction_id);
        $this->assertSame(1.0, (float) $transaction->number_of_shares);
        $this->assertSame(10.0, (float) $transaction->price_per_share);
    }
}
