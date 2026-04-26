<?php

namespace Domain\Transaction\Enums;

enum TransactionImportType: string
{
    case Trading212 = 'Trading 212';
    case InteractiveBrokers = 'Interactive Brokers';

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
