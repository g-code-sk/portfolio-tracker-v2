<?php

namespace Tests\Unit;

use App\Models\SecuritySplit;
use App\Models\Transaction;
use App\Services\PortfolioSecuritySplitAdjustmentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PortfolioSecuritySplitAdjustmentServiceTest extends TestCase
{
    public function test_it_returns_raw_amounts_when_no_splits_apply(): void
    {
        $service = new PortfolioSecuritySplitAdjustmentService;

        $transaction = Transaction::make([
            'executed_at' => Carbon::parse('2022-01-15'),
            'number_of_shares' => '10',
            'price_per_share' => '50',
        ]);

        $result = $service->adjust($transaction, collect());

        $this->assertSame(10.0, $result->numberOfShares);
        $this->assertSame(50.0, $result->pricePerShare);
    }

    public function test_it_applies_splits_that_occur_after_trade_day(): void
    {
        $service = new PortfolioSecuritySplitAdjustmentService;

        $transaction = Transaction::make([
            'executed_at' => Carbon::parse('2020-06-01 14:00:00'),
            'number_of_shares' => '100',
            'price_per_share' => '40',
        ]);

        $splits = new Collection([
            SecuritySplit::make([
                'effective_on' => '2020-08-31',
                'ratio_numerator' => 4,
                'ratio_denominator' => 1,
            ]),
        ]);

        $result = $service->adjust($transaction, $splits);

        $this->assertEqualsWithDelta(400.0, $result->numberOfShares, 1e-6);
        $this->assertEqualsWithDelta(10.0, $result->pricePerShare, 1e-6);
    }

    public function test_it_skips_splits_on_or_before_trade_day(): void
    {
        $service = new PortfolioSecuritySplitAdjustmentService;

        $transaction = Transaction::make([
            'executed_at' => Carbon::parse('2021-06-01'),
            'number_of_shares' => '10',
            'price_per_share' => '100',
        ]);

        $splits = new Collection([
            SecuritySplit::make([
                'effective_on' => '2021-06-01',
                'ratio_numerator' => 4,
                'ratio_denominator' => 1,
            ]),
        ]);

        $result = $service->adjust($transaction, $splits);

        $this->assertSame(10.0, $result->numberOfShares);
        $this->assertSame(100.0, $result->pricePerShare);
    }

    public function test_it_chains_multiple_splits(): void
    {
        $service = new PortfolioSecuritySplitAdjustmentService;

        $transaction = Transaction::make([
            'executed_at' => Carbon::parse('2019-01-01'),
            'number_of_shares' => '10',
            'price_per_share' => '400',
        ]);

        $splits = new Collection([
            SecuritySplit::make([
                'effective_on' => '2020-01-01',
                'ratio_numerator' => 2,
                'ratio_denominator' => 1,
            ]),
            SecuritySplit::make([
                'effective_on' => '2021-01-01',
                'ratio_numerator' => 2,
                'ratio_denominator' => 1,
            ]),
        ]);

        $result = $service->adjust($transaction, $splits);

        $this->assertEqualsWithDelta(40.0, $result->numberOfShares, 1e-6);
        $this->assertEqualsWithDelta(100.0, $result->pricePerShare, 1e-6);
    }

    public function test_it_skips_splits_without_parsed_ratio(): void
    {
        $service = new PortfolioSecuritySplitAdjustmentService;

        $transaction = Transaction::make([
            'executed_at' => Carbon::parse('2020-01-01'),
            'number_of_shares' => '10',
            'price_per_share' => '50',
        ]);

        $splits = new Collection([
            SecuritySplit::make([
                'effective_on' => '2022-01-01',
                'ratio_numerator' => null,
                'ratio_denominator' => null,
                'raw_ratio' => 'oops',
            ]),
        ]);

        $result = $service->adjust($transaction, $splits);

        $this->assertSame(10.0, $result->numberOfShares);
        $this->assertSame(50.0, $result->pricePerShare);
    }
}
