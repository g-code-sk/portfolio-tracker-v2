<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Domain\Security\Data\FinnhubQuoteData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FinnhubQuoteDataTest extends TestCase
{
    public static function emptyQuoteProvider(): array
    {
        return [
            'zeros' => [['c' => 0, 't' => 0], true],
            'non_numeric_price' => [['c' => null, 't' => 0], true],
            'missing_price_key' => [['t' => 0], true],
            'zero_price_with_usable_timestamp' => [['c' => 0.0, 't' => 1710000000], false],
            'non_zero_price_zero_timestamp' => [['c' => 10.5, 't' => 0], false],
            'valid_quote' => [['c' => 190.55, 't' => 1710000000], false],
        ];
    }

    #[DataProvider('emptyQuoteProvider')]
    public function test_is_empty_matches_finnhub_sentinel_payloads(array $payload, bool $expectedEmpty): void
    {
        $quote = FinnhubQuoteData::fromPayload($payload);

        $this->assertSame($expectedEmpty, $quote->isEmpty());
    }

    public function test_current_and_quoted_at_unix_read_payload_keys(): void
    {
        $quote = FinnhubQuoteData::fromPayload([
            'c' => 12.34,
            't' => 999,
            'pc' => 11.0,
        ]);

        $this->assertSame(12.34, $quote->currentPrice);
        $this->assertSame(999, $quote->quotedAtUnix);
    }

    public function test_quoted_at_uses_payload_unix_when_positive(): void
    {
        $quote = FinnhubQuoteData::fromPayload([
            'c' => 1.0,
            't' => 1710000500,
        ]);

        $this->assertTrue($quote->getQuotedAt()->equalTo(CarbonImmutable::createFromTimestampUTC(1710000500)));
    }

    public function test_quoted_at_falls_back_to_now_when_timestamp_not_positive(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-08 15:30:00', 'UTC'));

        try {
            $quote = FinnhubQuoteData::fromPayload([
                'c' => 1.0,
                't' => 0,
            ]);

            $this->assertTrue($quote->getQuotedAt()->equalTo(CarbonImmutable::parse('2026-05-08 15:30:00', 'UTC')));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_formatted_current_price_returns_decimal_string_with_ten_fraction_digits(): void
    {
        $quote = FinnhubQuoteData::fromPayload([
            'c' => 190.55,
            't' => 1,
        ]);

        $this->assertSame('190.5500000000', $quote->getFormattedCurrentPrice());
    }

    public function test_formatted_current_price_returns_null_when_price_not_numeric(): void
    {
        $quote = FinnhubQuoteData::fromPayload([
            'c' => null,
            't' => 1,
        ]);

        $this->assertNull($quote->getFormattedCurrentPrice());
    }

    public function test_has_price_is_true_when_current_price_is_numeric(): void
    {
        $quote = FinnhubQuoteData::fromPayload([
            'c' => 0.0,
            't' => 1710000000,
        ]);

        $this->assertTrue($quote->hasPrice());
    }

    public function test_has_price_is_false_when_current_price_is_not_numeric(): void
    {
        $quote = FinnhubQuoteData::fromPayload([
            'c' => null,
            't' => 0,
        ]);

        $this->assertFalse($quote->hasPrice());
    }
}
