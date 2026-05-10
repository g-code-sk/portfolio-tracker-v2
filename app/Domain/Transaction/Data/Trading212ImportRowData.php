<?php

namespace Domain\Transaction\Data;

use Domain\Transaction\Enums\Trading212TransactionType;
use Spatie\LaravelData\Data;

class Trading212ImportRowData extends Data
{
    public const HEADER_ACTION = 'Action';

    public const HEADER_TIME = 'Time';

    public const HEADER_ISIN = 'ISIN';

    public const HEADER_TICKER = 'Ticker';

    public const HEADER_NAME = 'Name';

    public const HEADER_ID = 'ID';

    public const HEADER_NUMBER_OF_SHARES = 'No. of shares';

    public const HEADER_PRICE_PER_SHARE = 'Price / share';

    public const HEADER_CURRENCY_PRICE_PER_SHARE = 'Currency (Price / share)';

    /**
     * Column labels that must be present in the export (Trading 212 wording). Order matches current exports for the required prefix; optional columns may follow.
     *
     * @var array<int, string>
     */
    public const REQUIRED_HEADERS = [
        self::HEADER_ACTION,
        self::HEADER_TIME,
        self::HEADER_ISIN,
        self::HEADER_TICKER,
        self::HEADER_NAME,
        self::HEADER_ID,
        self::HEADER_NUMBER_OF_SHARES,
        self::HEADER_PRICE_PER_SHARE,
        self::HEADER_CURRENCY_PRICE_PER_SHARE,
    ];

    public function __construct(
        public ?Trading212TransactionType $action,
        public ?string $time,
        public ?string $isin,
        public ?string $ticker,
        public ?string $name,
        public ?string $externalTransactionId,
        public ?string $numberOfShares,
        public ?string $pricePerShare,
        public ?string $currencyPricePerShare,
    ) {}

    /**
     * @param  array<int, string>  $row
     * @param  array<string, int>  $headerIndexMap  normalized header label => column index
     */
    public static function fromHeaderMappedRow(array $row, array $headerIndexMap): self
    {
        $cell = static function (string $header) use ($row, $headerIndexMap): mixed {
            if (! array_key_exists($header, $headerIndexMap)) {
                return null;
            }

            return $row[$headerIndexMap[$header]] ?? null;
        };

        $action = self::normalizeNullableString($cell(self::HEADER_ACTION));

        return new self(
            action: $action === null ? null : Trading212TransactionType::tryFrom($action),
            time: self::normalizeNullableString($cell(self::HEADER_TIME)),
            isin: self::normalizeNullableString($cell(self::HEADER_ISIN)),
            ticker: self::normalizeNullableString($cell(self::HEADER_TICKER)),
            name: self::normalizeNullableString($cell(self::HEADER_NAME)),
            externalTransactionId: self::normalizeNullableString($cell(self::HEADER_ID)),
            numberOfShares: self::normalizeNullableString($cell(self::HEADER_NUMBER_OF_SHARES)),
            pricePerShare: self::normalizeNullableString($cell(self::HEADER_PRICE_PER_SHARE)),
            currencyPricePerShare: self::normalizeNullableString($cell(self::HEADER_CURRENCY_PRICE_PER_SHARE)),
        );
    }

    private static function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    public function hasTicker(): bool
    {
        return $this->ticker !== null;
    }

    public function hasExternalTransactionId(): bool
    {
        return $this->externalTransactionId !== null;
    }

    public function hasTime(): bool
    {
        return $this->time !== null;
    }

    public function hasShareAndPriceFields(): bool
    {
        return $this->numberOfShares !== null && $this->pricePerShare !== null;
    }

    public function hasCurrencyPricePerShare(): bool
    {
        return $this->currencyPricePerShare !== null;
    }

    public function hasThreeLetterPriceCurrency(): bool
    {
        return $this->currencyPricePerShare !== null && strlen($this->currencyPricePerShare) === 3;
    }

    public function hasNumericShareAndPrice(): bool
    {
        if ($this->numberOfShares === null || $this->pricePerShare === null) {
            return false;
        }

        return is_numeric($this->numberOfShares) && is_numeric($this->pricePerShare);
    }

    public function hasIsin(): bool
    {
        return $this->isin !== null;
    }
}
