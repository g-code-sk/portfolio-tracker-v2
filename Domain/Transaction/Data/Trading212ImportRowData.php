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
      public ?string $externalTransactionId,
      public ?string $numberOfShares,
      public ?string $pricePerShare,
      public ?string $currencyPricePerShare,
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
         externalTransactionId: self::normalizeNullableString($row[6] ?? null),
         numberOfShares: self::normalizeNullableString($row[7] ?? null),
         pricePerShare: self::normalizeNullableString($row[8] ?? null),
         currencyPricePerShare: self::normalizeNullableString($row[9] ?? null),
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
