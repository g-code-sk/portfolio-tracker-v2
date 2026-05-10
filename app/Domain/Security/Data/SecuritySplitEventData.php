<?php

namespace Domain\Security\Data;

use Carbon\CarbonImmutable;
use Domain\Security\Enums\SecurityDataProviderCode;
use Scheb\YahooFinanceApi\Results\SplitData;

final readonly class SecuritySplitEventData
{
    public function __construct(
        public CarbonImmutable $effectiveOn,
        public ?string $rawRatio,
        public ?int $ratioNumerator,
        public ?int $ratioDenominator,
        public SecurityDataProviderCode $providerCode,
    ) {}

    public static function fromYahooSplitData(SplitData $split): self
    {
        $date = $split->getDate();
        $effectiveOn = CarbonImmutable::instance($date)->utc()->startOfDay();

        $rawRatio = $split->getStockSplits();
        $trimmedRaw = is_string($rawRatio) ? trim($rawRatio) : null;
        $normalizedRaw = ($trimmedRaw === '' || $trimmedRaw === null) ? null : $trimmedRaw;

        [$numerator, $denominator] = self::parseRatioString($normalizedRaw);

        return new self(
            effectiveOn: $effectiveOn,
            rawRatio: $normalizedRaw,
            ratioNumerator: $numerator,
            ratioDenominator: $denominator,
            providerCode: SecurityDataProviderCode::Yahoo,
        );
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private static function parseRatioString(?string $ratio): array
    {
        if ($ratio === null) {
            return [null, null];
        }

        $parts = explode(':', $ratio, 2);

        if (count($parts) !== 2) {
            return [null, null];
        }

        $nominatorString = trim($parts[0]);
        $denominatorString = trim($parts[1]);

        if (! ctype_digit($nominatorString) || ! ctype_digit($denominatorString)) {
            return [null, null];
        }

        $nominator = (int) $nominatorString;
        $denominator = (int) $denominatorString;

        if ($nominator < 1 || $denominator < 1) {
            return [null, null];
        }

        return [$nominator, $denominator];
    }
}
