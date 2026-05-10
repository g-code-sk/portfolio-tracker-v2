<?php

namespace Tests\Unit;

use Domain\Transaction\Data\WholeShareBucketResponseData;
use Domain\Transaction\Data\WholeShareSegmentResponseData;
use PHPUnit\Framework\TestCase;

class WholeShareBucketResponseDataTest extends TestCase
{
    public function test_has_segments(): void
    {
        $empty = new WholeShareBucketResponseData(0, []);
        $this->assertFalse($empty->hasSegments());

        $bucket = new WholeShareBucketResponseData(1, [
            new WholeShareSegmentResponseData(
                sourceTransactionId: 1,
                externalTransactionId: 'ext-1',
                executedAt: '2024-01-01T12:00:00+00:00',
                ticker: 'AAA',
                name: 'AAA Inc',
                numberOfShares: 1.0,
                pricePerShare: 10.0,
                totalAmount: 10.0,
                currencySymbol: 'USD',
            ),
        ]);
        $this->assertTrue($bucket->hasSegments());
        $this->assertFalse($bucket->isEmpty());
    }

    public function test_is_empty(): void
    {
        $empty = new WholeShareBucketResponseData(0, []);
        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($empty->hasSegments());
    }
}
