<?php

namespace Tests\Unit;

use Domain\Portfolio\Data\PortfolioPositionResponseData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortfolioPositionResponseDataTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private static function aggregatedRow(array $overrides = []): object
    {
        return (object) array_merge([
            'security_id' => 1,
            'currency_id' => 1,
            'ticker' => 'TST',
            'name' => 'Test Security',
            'currency' => 'USD',
            'shares_bought' => 10.0,
            'shares_sold' => 10.0,
            'invested_amount' => 1000.0,
            'sold_amount' => 1200.0,
            'total_shares' => 0.0,
            'current_price' => 150.0,
            'current_price_currency' => 'USD',
        ], $overrides);
    }

    public function test_closed_position_computes_realized_gain_and_percent(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'total_shares' => 0.0,
            'invested_amount' => 1000.0,
            'sold_amount' => 1200.0,
        ]));

        $this->assertSame(200.0, $dto->totalGainLossAmount);
        $this->assertSame(20.0, $dto->totalReturnPercent);
    }

    public function test_open_position_includes_mark_to_market_value(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'shares_bought' => 10.0,
            'shares_sold' => 5.0,
            'invested_amount' => 1000.0,
            'sold_amount' => 600.0,
            'total_shares' => 5.0,
            'current_price' => 110.0,
            'current_price_currency' => 'USD',
            'currency' => 'USD',
        ]));

        $this->assertSame(150.0, $dto->totalGainLossAmount);
        $this->assertSame(15.0, $dto->totalReturnPercent);
    }

    public function test_open_position_without_price_returns_null_metrics(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'shares_bought' => 10.0,
            'shares_sold' => 5.0,
            'total_shares' => 5.0,
            'current_price' => null,
            'current_price_currency' => null,
        ]));

        $this->assertNull($dto->totalGainLossAmount);
        $this->assertNull($dto->totalReturnPercent);
    }

    public function test_currency_mismatch_while_holding_returns_null_metrics(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'currency' => 'EUR',
            'shares_bought' => 10.0,
            'shares_sold' => 5.0,
            'total_shares' => 5.0,
            'current_price' => 100.0,
            'current_price_currency' => 'USD',
        ]));

        $this->assertNull($dto->totalGainLossAmount);
        $this->assertNull($dto->totalReturnPercent);
    }

    public function test_invested_amount_not_positive_returns_null_metrics(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'invested_amount' => 0.0,
            'sold_amount' => 100.0,
            'total_shares' => 0.0,
        ]));

        $this->assertNull($dto->totalGainLossAmount);
        $this->assertNull($dto->totalReturnPercent);
    }

    #[DataProvider('emptyQuoteCurrencyProvider')]
    public function test_open_position_requires_quote_currency_when_price_present(mixed $currencyValue): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'total_shares' => 3.0,
            'current_price' => 50.0,
            'current_price_currency' => $currencyValue,
        ]));

        $this->assertNull($dto->totalGainLossAmount);
        $this->assertNull($dto->totalReturnPercent);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function emptyQuoteCurrencyProvider(): array
    {
        return [
            'null' => [null],
            'empty_string' => [''],
        ];
    }
}
