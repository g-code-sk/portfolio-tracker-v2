<?php

namespace Domain\Transaction\Enums;

enum TransactionImportType: string
{
    case Trading212 = 'Trading 212';
    case InteractiveBrokers = 'Interactive Brokers';

    public function isTrading212Type(): bool
    {
        return $this === self::Trading212;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $importType): string => $importType->value,
            self::cases()
        );
    }
}
