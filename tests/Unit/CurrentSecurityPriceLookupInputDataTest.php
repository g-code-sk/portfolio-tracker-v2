<?php

namespace Tests\Unit;

use App\Models\Security;
use Domain\Security\Data\CurrentSecurityPriceLookupInputData;
use PHPUnit\Framework\TestCase;

class CurrentSecurityPriceLookupInputDataTest extends TestCase
{
    public function test_try_from_normalizes_fields(): void
    {
        $dto = CurrentSecurityPriceLookupInputData::tryFrom('  aapl  ', '  Apple  ', '  us0378331005  ');
        $this->assertNotNull($dto);
        $this->assertSame('aapl', $dto->ticker);
        $this->assertSame('AAPL', $dto->tickerUpper);
        $this->assertSame('Apple', $dto->normalizedDisplayName);
        $this->assertSame('US0378331005', $dto->normalizedIsin);
    }

    public function test_try_from_returns_null_for_blank_ticker(): void
    {
        $this->assertNull(CurrentSecurityPriceLookupInputData::tryFrom('   ', null, null));
    }

    public function test_try_from_maps_empty_name_and_isin_to_null(): void
    {
        $dto = CurrentSecurityPriceLookupInputData::tryFrom('X', '', '');
        $this->assertNotNull($dto);
        $this->assertNull($dto->normalizedDisplayName);
        $this->assertNull($dto->normalizedIsin);
    }

    public function test_try_from_maps_whitespace_only_name_to_null(): void
    {
        $dto = CurrentSecurityPriceLookupInputData::tryFrom('X', '   ', null);
        $this->assertNotNull($dto);
        $this->assertNull($dto->normalizedDisplayName);
    }

    public function test_has_non_empty_normalized_display_name(): void
    {
        $withName = CurrentSecurityPriceLookupInputData::tryFrom('X', 'Co', null);
        $this->assertNotNull($withName);
        $this->assertTrue($withName->hasNonEmptyNormalizedDisplayName());

        $withoutName = CurrentSecurityPriceLookupInputData::tryFrom('X', null, null);
        $this->assertNotNull($withoutName);
        $this->assertFalse($withoutName->hasNonEmptyNormalizedDisplayName());
    }

    public function test_should_disambiguate_by_normalized_name(): void
    {
        $dto = CurrentSecurityPriceLookupInputData::tryFrom('X', 'Name', null);
        $this->assertNotNull($dto);

        $this->assertFalse($dto->shouldDisambiguateByNormalizedName(1));

        $noDisplayName = CurrentSecurityPriceLookupInputData::tryFrom('X', null, null);
        $this->assertNotNull($noDisplayName);
        $this->assertFalse($noDisplayName->shouldDisambiguateByNormalizedName(2));

        $this->assertTrue($dto->shouldDisambiguateByNormalizedName(2));
    }

    public function test_try_from_security_delegates_to_try_from(): void
    {
        $security = new Security([
            'ticker' => '  MSFT  ',
            'name' => ' Microsoft ',
            'isin' => ' US5949181045 ',
        ]);

        $dto = CurrentSecurityPriceLookupInputData::tryFromSecurity($security);
        $this->assertNotNull($dto);
        $this->assertSame('MSFT', $dto->ticker);
        $this->assertSame('Microsoft', $dto->normalizedDisplayName);
        $this->assertSame('US5949181045', $dto->normalizedIsin);
    }
}
