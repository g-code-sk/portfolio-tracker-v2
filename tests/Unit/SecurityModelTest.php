<?php

namespace Tests\Unit;

use App\Models\Security;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SecurityModelTest extends TestCase
{
    public function test_is_current_price_stale_when_never_updated(): void
    {
        $security = new Security;
        $security->current_price_updated_at = null;

        $this->assertTrue($security->isCurrentPriceStale(Carbon::now(), 24));
    }

    public function test_is_current_price_stale_respects_ttl(): void
    {
        $security = new Security;
        $now = Carbon::parse('2026-05-01 12:00:00');
        $security->current_price_updated_at = $now->copy()->subHours(25);

        $this->assertTrue($security->isCurrentPriceStale($now, 24));

        $security->current_price_updated_at = $now->copy()->subHours(1);

        $this->assertFalse($security->isCurrentPriceStale($now, 24));
    }

    public function test_has_placeholder_name_compared_to_ticker(): void
    {
        $s = new Security(['name' => 'AAPL', 'ticker' => 'AAPL']);
        $this->assertTrue($s->hasPlaceholderNameComparedToTicker('AAPL'));

        $s = new Security(['name' => 'Apple Inc', 'ticker' => 'AAPL']);
        $this->assertFalse($s->hasPlaceholderNameComparedToTicker('AAPL'));

        $s = new Security(['name' => '', 'ticker' => 'AAPL']);
        $this->assertTrue($s->hasPlaceholderNameComparedToTicker('AAPL'));
    }
}
