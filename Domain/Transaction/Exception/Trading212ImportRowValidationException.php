<?php

namespace Domain\Transaction\Exception;

use Domain\Transaction\Data\Trading212ImportRowData;
use Illuminate\Validation\ValidationException;
use JsonException;

abstract class Trading212ImportRowValidationException extends ValidationException
{
    abstract protected static function defaultMessage(): string;

    /**
     * @return array<int, string>
     */
    final protected static function fileMessagesForOptionalImportRow(
        ?Trading212ImportRowData $importRow,
        string $message,
    ): array {
        return $importRow instanceof Trading212ImportRowData
            ? self::fileMessagesForImportRow(importRow: $importRow, message: $message)
            : [$message];
    }

    /**
     * @return array<int, string>
     */
    final protected static function fileMessagesForImportRow(
        Trading212ImportRowData $importRow,
        string $message,
    ): array {
        $payload = $importRow->toArray();

        try {
            $rowJson = json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException) {
            $rowJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return [
            $message,
            'import_row: '.$rowJson,
        ];
    }

    public static function fromImportRow(
        Trading212ImportRowData $importRow,
        ?string $message = null,
    ): static {
        /** @var static $exception */
        $exception = static::withMessages([
            'file' => self::fileMessagesForImportRow(
                importRow: $importRow,
                message: $message ?? static::defaultMessage(),
            ),
        ]);

        return $exception;
    }
}
