<?php

namespace Domain\Security\Data;

use App\Models\Security;

final readonly class CurrentSecurityPriceLookupInputData
{
    private function __construct(
        public string $ticker,
        public string $tickerUpper,
        public ?string $normalizedDisplayName,
        public ?string $normalizedIsin,
    ) {}

    public static function tryFrom(?string $ticker, ?string $name, ?string $isin): ?self
    {
        $trimmedTicker = trim((string) $ticker);

        if ($trimmedTicker === '') {
            return null;
        }

        $displayName = $name !== null ? trim($name) : null;

        if ($displayName === '') {
            $displayName = null;
        }

        $normalizedIsin = $isin !== null ? strtoupper(trim($isin)) : null;

        if ($normalizedIsin === '') {
            $normalizedIsin = null;
        }

        return new self(
            ticker: $trimmedTicker,
            tickerUpper: strtoupper($trimmedTicker),
            normalizedDisplayName: $displayName,
            normalizedIsin: $normalizedIsin,
        );
    }

    public static function tryFromSecurity(Security $security): ?self
    {
        return self::tryFrom($security->ticker, $security->name, $security->isin);
    }

    public function hasNonEmptyNormalizedDisplayName(): bool
    {
        return $this->normalizedDisplayName !== null;
    }
}
