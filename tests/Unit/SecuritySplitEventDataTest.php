<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Domain\Security\Data\SecuritySplitEventData;
use Domain\Security\Enums\SecurityDataProviderCode;
use Scheb\YahooFinanceApi\Results\SplitData;
use Tests\TestCase;

class SecuritySplitEventDataTest extends TestCase
{
    public function test_it_maps_yahoo_split_payload(): void
    {
        $split = new SplitData(new \DateTime('2020-08-31 12:00:00'), '4:1');

        $dto = SecuritySplitEventData::fromYahooSplitData($split);

        $this->assertTrue($dto->effectiveOn->equalTo(CarbonImmutable::parse('2020-08-31')->utc()->startOfDay()));
        $this->assertSame('4:1', $dto->rawRatio);
        $this->assertSame(4, $dto->ratioNumerator);
        $this->assertSame(1, $dto->ratioDenominator);
        $this->assertSame(SecurityDataProviderCode::Yahoo, $dto->providerCode);
    }

    public function test_it_parses_reverse_split_ratio(): void
    {
        $split = new SplitData(new \DateTime('2023-01-01'), '1:10');

        $dto = SecuritySplitEventData::fromYahooSplitData($split);

        $this->assertSame(1, $dto->ratioNumerator);
        $this->assertSame(10, $dto->ratioDenominator);
    }

    public function test_it_normalizes_empty_ratio_string_to_null_raw(): void
    {
        $split = new SplitData(new \DateTime('2021-06-01'), '');

        $dto = SecuritySplitEventData::fromYahooSplitData($split);

        $this->assertNull($dto->rawRatio);
        $this->assertNull($dto->ratioNumerator);
        $this->assertNull($dto->ratioDenominator);
    }

    public function test_it_keeps_raw_but_nulls_parsed_values_when_ratio_format_is_invalid(): void
    {
        $split = new SplitData(new \DateTime('2021-06-01'), '4-for-1');

        $dto = SecuritySplitEventData::fromYahooSplitData($split);

        $this->assertSame('4-for-1', $dto->rawRatio);
        $this->assertNull($dto->ratioNumerator);
        $this->assertNull($dto->ratioDenominator);
    }

    public function test_it_keeps_raw_but_nulls_parsed_values_when_parts_are_non_numeric(): void
    {
        $split = new SplitData(new \DateTime('2021-06-01'), 'a:b');

        $dto = SecuritySplitEventData::fromYahooSplitData($split);

        $this->assertSame('a:b', $dto->rawRatio);
        $this->assertNull($dto->ratioNumerator);
        $this->assertNull($dto->ratioDenominator);
    }

    public function test_it_handles_null_ratio_from_yahoo(): void
    {
        $split = new SplitData(new \DateTime('2021-06-01'), null);

        $dto = SecuritySplitEventData::fromYahooSplitData($split);

        $this->assertNull($dto->rawRatio);
        $this->assertNull($dto->ratioNumerator);
        $this->assertNull($dto->ratioDenominator);
    }
}
