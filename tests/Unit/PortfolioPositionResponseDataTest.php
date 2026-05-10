<?php

namespace Tests\Unit;

use Domain\Portfolio\Data\PortfolioPositionAggregatedRowData;
use Domain\Portfolio\Data\PortfolioPositionResponseData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortfolioPositionResponseDataTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private static function aggregatedRow(array $overrides = []): PortfolioPositionAggregatedRowData
    {
        $defaults = [
            'securityId' => 1,
            'currencyId' => 1,
            'ticker' => 'TST',
            'name' => 'Test Security',
            'currencySymbol' => 'USD',
            'sharesBought' => 10.0,
            'sharesSold' => 10.0,
            'investedAmount' => 1000.0,
            'soldAmount' => 1200.0,
            'totalShares' => 0.0,
            'currentPrice' => 150.0,
            'currentPriceCurrency' => 'USD',
        ];

        return new PortfolioPositionAggregatedRowData(...array_merge($defaults, $overrides));
    }

    public function test_closed_position_computes_realized_gain_and_percent(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'totalShares' => 0.0,
            'investedAmount' => 1000.0,
            'soldAmount' => 1200.0,
        ]));

        $this->assertSame(200.0, $dto->totalGainLossAmount);
        $this->assertSame(20.0, $dto->totalReturnPercent);
    }

    public function test_open_position_includes_mark_to_market_value(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'sharesBought' => 10.0,
            'sharesSold' => 5.0,
            'investedAmount' => 1000.0,
            'soldAmount' => 600.0,
            'totalShares' => 5.0,
            'currentPrice' => 110.0,
            'currentPriceCurrency' => 'USD',
            'currencySymbol' => 'USD',
        ]));

        $this->assertSame(150.0, $dto->totalGainLossAmount);
        $this->assertSame(15.0, $dto->totalReturnPercent);
    }

    public function test_open_position_without_price_returns_null_metrics(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'sharesBought' => 10.0,
            'sharesSold' => 5.0,
            'totalShares' => 5.0,
            'currentPrice' => null,
            'currentPriceCurrency' => null,
        ]));

        $this->assertNull($dto->totalGainLossAmount);
        $this->assertNull($dto->totalReturnPercent);
    }

    public function test_currency_mismatch_while_holding_returns_null_metrics(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'currencySymbol' => 'EUR',
            'sharesBought' => 10.0,
            'sharesSold' => 5.0,
            'totalShares' => 5.0,
            'currentPrice' => 100.0,
            'currentPriceCurrency' => 'USD',
        ]));

        $this->assertNull($dto->totalGainLossAmount);
        $this->assertNull($dto->totalReturnPercent);
    }

    public function test_invested_amount_not_positive_returns_null_metrics(): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'investedAmount' => 0.0,
            'soldAmount' => 100.0,
            'totalShares' => 0.0,
        ]));

        $this->assertNull($dto->totalGainLossAmount);
        $this->assertNull($dto->totalReturnPercent);
    }

    #[DataProvider('emptyQuoteCurrencyProvider')]
    public function test_open_position_requires_quote_currency_when_price_present(mixed $currencyValue): void
    {
        $dto = PortfolioPositionResponseData::fromAggregatedRow(self::aggregatedRow([
            'totalShares' => 3.0,
            'currentPrice' => 50.0,
            'currentPriceCurrency' => $currencyValue,
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
