<?php

namespace Tests\Unit;

use Domain\Security\Data\FinnhubSymbolSearchHitData;
use Tests\TestCase;

class FinnhubSymbolSearchHitDataTest extends TestCase
{
    public function test_try_from_row_maps_optional_fields(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'NYSE:FL',
            'displaySymbol' => 'FL',
            'description' => 'Foot Locker Inc',
            'type' => 'Common Stock',
            'isin' => 'us3448491049',
        ]);

        $this->assertNotNull($hit);
        $this->assertSame('NYSE:FL', $hit->symbol);
        $this->assertSame('FL', $hit->displaySymbol);
        $this->assertSame('Foot Locker Inc', $hit->description);
        $this->assertSame('Common Stock', $hit->type);
        $this->assertSame('US3448491049', $hit->isin);
    }

    public function test_try_from_row_returns_null_for_non_array(): void
    {
        $this->assertNull(FinnhubSymbolSearchHitData::tryFromRow(null));
        $this->assertNull(FinnhubSymbolSearchHitData::tryFromRow('NYSE:FL'));
    }

    public function test_try_from_row_returns_null_when_symbol_missing_or_blank(): void
    {
        $this->assertNull(FinnhubSymbolSearchHitData::tryFromRow([
            'displaySymbol' => 'FL',
        ]));
        $this->assertNull(FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => '',
        ]));
        $this->assertNull(FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => '   ',
        ]));
    }

    public function test_try_from_row_sets_null_optionals_when_absent(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'AAPL',
        ]);

        $this->assertNotNull($hit);
        $this->assertSame('AAPL', $hit->symbol);
        $this->assertNull($hit->displaySymbol);
        $this->assertNull($hit->description);
        $this->assertNull($hit->type);
        $this->assertNull($hit->isin);
        $this->assertFalse($hit->hasInstrumentType());
    }

    public function test_has_instrument_type_is_true_when_type_present(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
            'type' => 'Common Stock',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->hasInstrumentType());
    }

    public function test_is_equity_like_instrument_type_true_when_type_missing(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->isEquityLikeInstrumentType());
    }

    public function test_is_equity_like_instrument_type_true_for_common_stock(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
            'type' => 'Common Stock',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->isEquityLikeInstrumentType());
    }

    public function test_is_equity_like_instrument_type_false_when_type_contains_crypto_marker(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'BTC',
            'type' => 'Crypto',
        ]);

        $this->assertNotNull($hit);
        $this->assertFalse($hit->isEquityLikeInstrumentType());
    }

    public function test_is_equity_like_instrument_type_false_when_type_contains_forex_marker(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'OANDA:EUR_USD',
            'type' => 'Forex',
        ]);

        $this->assertNotNull($hit);
        $this->assertFalse($hit->isEquityLikeInstrumentType());
    }

    public function test_matches_ticker_upper_via_display_symbol(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'NYSE:FL',
            'displaySymbol' => 'FL',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->matchesTicker('FL'));
        $this->assertFalse($hit->matchesTicker('MSFT'));
    }

    public function test_matches_ticker_upper_via_qualified_symbol_when_display_absent(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'NYSE:FL',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->matchesTicker('FL'));
    }

    public function test_matches_ticker_upper_via_plain_symbol(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'AAPL',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->matchesTicker('AAPL'));
        $this->assertFalse($hit->matchesTicker('MSFT'));
    }

    public function test_matches_normalized_isin_when_portfolio_has_no_isin(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
            'isin' => 'US0378331005',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->matchesIsin(null));
    }

    public function test_matches_normalized_isin_when_hit_and_portfolio_agree(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
            'isin' => 'us0378331005',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->matchesIsin('US0378331005'));
    }

    public function test_matches_normalized_isin_false_when_hit_isin_missing_but_portfolio_requires(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
        ]);

        $this->assertNotNull($hit);
        $this->assertFalse($hit->matchesIsin('US0378331005'));
    }

    public function test_matches_normalized_isin_false_when_hit_isin_differs(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
            'isin' => 'US1111111111',
        ]);

        $this->assertNotNull($hit);
        $this->assertFalse($hit->matchesIsin('US0378331005'));
    }

    public function test_matches_normalized_name_tokens_true_when_description_contains_tokens(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'NYSE:FL',
            'description' => 'Foot Locker Inc',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->matchesNormalizedNameTokens('Foot Locker Inc.'));
    }

    public function test_matches_normalized_name_tokens_false_when_description_missing(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
        ]);

        $this->assertNotNull($hit);
        $this->assertFalse($hit->matchesNormalizedNameTokens('Apple Inc.'));
    }

    public function test_matches_normalized_name_tokens_false_when_token_missing_from_description(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
            'description' => 'Wrong Company Name',
        ]);

        $this->assertNotNull($hit);
        $this->assertFalse($hit->matchesNormalizedNameTokens('Foot Locker Inc.'));
    }

    public function test_matches_normalized_name_tokens_true_when_only_stopwords_remain(): void
    {
        $hit = FinnhubSymbolSearchHitData::tryFromRow([
            'symbol' => 'X',
            'description' => 'Anything',
        ]);

        $this->assertNotNull($hit);
        $this->assertTrue($hit->matchesNormalizedNameTokens('Inc.'));
    }
}
