<?php

namespace Domain\Transaction\Action;

use Domain\Transaction\Data\Trading212ImportRowData;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;

class ParseTrading212CsvAction
{
    /**
     * @var array<int, string>
     */
    private const EXPECTED_HEADERS = [
        'Action',
        'Time',
        'ISIN',
        'Ticker',
        'Name',
        'Notes',
        'ID',
        'No. of shares',
        'Price / share',
        'Currency (Price / share)',
        'Exchange rate',
        'Result',
        'Currency (Result)',
        'Total',
        'Currency (Total)',
        'Withholding tax',
        'Currency (Withholding tax)',
        'Currency conversion fee',
        'Currency (Currency conversion fee)',
    ];

    /**
     * @return Collection<int, Trading212ImportRowData>
     */
    public function execute(string $storedFilePath): Collection
    {
        $sheetRows = Excel::toCollection(
            new class implements ToCollection
            {
                public function collection(Collection $collection): void {}
            },
            $storedFilePath,
            'local',
        )->first();

        if (! $sheetRows instanceof Collection || $sheetRows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => ['The import file is empty.'],
            ]);
        }

        $headerRow = $this->normalizeRow($sheetRows->first());

        if ($headerRow !== self::EXPECTED_HEADERS) {
            throw ValidationException::withMessages([
                'file' => ['The uploaded file is not a valid Trading 212 export format.'],
            ]);
        }

        return $sheetRows
            ->slice(1)
            ->values()
            ->map(fn (mixed $row): array => $this->normalizeRow($row))
            ->filter(fn (array $row): bool => $this->hasRowAnyValue($row))
            ->map(fn (array $row): Trading212ImportRowData => Trading212ImportRowData::fromCsvRow($row))
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function normalizeRow(mixed $row): array
    {
        $rowArray = $row instanceof Collection ? $row->toArray() : (array) $row;
        $normalizedRow = array_values(array_map(
            static fn (mixed $value): string => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"),
            $rowArray,
        ));

        return $normalizedRow;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function hasRowAnyValue(array $row): bool
    {
        return collect($row)->contains(static fn (string $value): bool => $value !== '');
    }
}
