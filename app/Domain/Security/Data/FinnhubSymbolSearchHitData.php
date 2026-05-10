<?php

namespace Domain\Security\Data;

use Illuminate\Support\Str;

final readonly class FinnhubSymbolSearchHitData
{
    /** @var list<string> */
    private const array OBVIOUS_NON_EQUITY_TYPE_MARKERS = [
        'currency',
        'crypto',
        'forex',
        'fx',
        'commodity',
        'index',
    ];

    /** @var list<string> */
    private const array NAME_STOPWORDS = [
        'inc',
        'inc.',
        'corp',
        'corp.',
        'corporation',
        'ltd',
        'ltd.',
        'plc',
        'nv',
        'sa',
        'ag',
        'llc',
        'co.',
        'co',
        'the',
    ];

    private function __construct(
        public string $symbol,
        public ?string $displaySymbol,
        public ?string $description,
        public ?string $type,
        public ?string $isin,
    ) {}

    /**
     * Whether this search hit matches the portfolio ticker and optional ISIN (equity-like instrument filter included).
     */
    public function matchSymbol(CurrentSecurityPriceLookupInputData $lookup): bool
    {
        if (! $this->isEquityLikeInstrumentType()) {
            return false;
        }

        if (! $this->matchesTicker($lookup->tickerUpper)) {
            return false;
        }

        return $this->matchesIsin($lookup->normalizedIsin);
    }

    public static function tryFromRow(mixed $row): ?self
    {
        if (! is_array($row)) {
            return null;
        }

        $symbolRaw = data_get($row, 'symbol');

        if (! is_string($symbolRaw)) {
            return null;
        }

        $symbol = trim($symbolRaw);

        if ($symbol === '') {
            return null;
        }

        return new self(
            symbol: $symbol,
            displaySymbol: self::getOptionalString(data_get($row, 'displaySymbol')),
            description: self::getOptionalString(data_get($row, 'description')),
            type: self::getOptionalString(data_get($row, 'type')),
            isin: self::getOptionalIsin(data_get($row, 'isin')),
        );
    }

    private static function getOptionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function getOptionalIsin(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : strtoupper($trimmed);
    }

    /**
     * Finnhub omits or blanks `type` on some rows; we cannot classify those as non-equity.
     */
    public function hasInstrumentType(): bool
    {
        return $this->type !== null;
    }

    /**
     * Heuristic for Finnhub `type`: missing type is treated as not obviously non-equity.
     */
    public function isEquityLikeInstrumentType(): bool
    {
        if (! $this->hasInstrumentType()) {
            return true;
        }

        $typeLower = mb_strtolower($this->type);

        foreach (self::OBVIOUS_NON_EQUITY_TYPE_MARKERS as $marker) {
            if (str_contains($typeLower, $marker)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  string  $ticker  Uppercase normalized portfolio ticker (trimmed).
     */
    public function matchesTicker(string $ticker): bool
    {
        $normalizedTicker = $this->normalizeString($ticker);
        $normalizedDisplaySymbol = $this->normalizeString($this->displaySymbol);

        if ($normalizedDisplaySymbol !== null && $normalizedDisplaySymbol === $normalizedTicker) {
            return true;
        }

        if ($this->doesTickerHaveSymbolSuffix()) {
            $tickerSuffix = $this->getTickerFromSymbol();

            return $this->normalizeString($tickerSuffix) === $normalizedTicker;
        }

        return $this->normalizeString($this->symbol) === $normalizedTicker;
    }

    public function getTickerFromSymbol(): string
    {
        return Str::afterLast($this->symbol, ':');
    }

    public function doesTickerHaveSymbolSuffix(): bool
    {
        return str_contains($this->symbol, ':');
    }

    /**
     * @param  ?string  $isin  Uppercase trimmed portfolio ISIN, or null when not filtering by ISIN.
     */
    public function matchesIsin(?string $isin): bool
    {
        if ($isin === null) {
            return true;
        }

        if (! $this->hasIsin()) {
            return false;
        }

        return $this->normalizeString($this->isin) === $this->normalizeString($isin);
    }

    public function hasIsin(): bool
    {
        return $this->isin !== null;
    }

    public function hasDescription(): bool
    {
        return $this->description !== null && $this->description !== '';
    }

    /**
     * Significant tokens from {@see $normalizedName} must appear as substrings in Finnhub's description (tie-break when multiple hits share ticker).
     *
     * @param  string  $normalizedName  Trimmed security display name from the portfolio.
     */
    public function matchesNormalizedNameTokens(string $normalizedName): bool
    {
        if (! $this->hasDescription()) {
            return false;
        }

        $descLower = mb_strtolower($this->description);
        $nameTokens = preg_split('/\s+/u', mb_strtolower(trim($normalizedName))) ?: [];
        $significantTokens = [];

        foreach ($nameTokens as $token) {
            if ($token === '' || mb_strlen($token) < 2) {
                continue;
            }

            if (in_array($token, self::NAME_STOPWORDS, true)) {
                continue;
            }

            $significantTokens[] = $token;
        }

        if ($significantTokens === []) {
            return true;
        }

        foreach ($significantTokens as $token) {
            if (! str_contains($descLower, $token)) {
                return false;
            }
        }

        return true;
    }

    public function normalizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(strtoupper($value));
    }
}
