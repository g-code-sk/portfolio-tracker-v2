<?php

namespace Domain\Transaction\Data;

use Domain\Transaction\Enums\Trading212TransactionType;
use Spatie\LaravelData\Data;

class Trading212ImportRowData extends Data
{
    public function __construct(
        public ?Trading212TransactionType $action,
        public ?string $isin,
        public ?string $ticker,
        public ?string $name,
        public ?string $external_transaction_id,
        public ?string $number_of_shares,
        public ?string $price_per_share,
        public ?string $currency_price_per_share,
    ) {}

    /**
     * @param  array<int, mixed>  $row
     */
    public static function fromCsvRow(array $row): self
    {
        $action = self::normalizeNullableString($row[0] ?? null);

        return new self(
            action: $action === null ? null : Trading212TransactionType::tryFrom($action),
            isin: self::normalizeNullableString($row[2] ?? null),
            ticker: self::normalizeNullableString($row[3] ?? null),
            name: self::normalizeNullableString($row[4] ?? null),
            external_transaction_id: self::normalizeNullableString($row[6] ?? null),
            number_of_shares: self::normalizeNullableString($row[7] ?? null),
            price_per_share: self::normalizeNullableString($row[8] ?? null),
            currency_price_per_share: self::normalizeNullableString($row[9] ?? null),
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
}
