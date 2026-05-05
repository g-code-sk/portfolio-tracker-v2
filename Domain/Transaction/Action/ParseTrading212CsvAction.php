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
        $headerIndexMap = $this->buildHeaderIndexMap($headerRow);

        $this->validateRequiredHeaders($headerIndexMap);

        return $sheetRows
            ->slice(1)
            ->values()
            ->map(fn (mixed $row): array => $this->normalizeRow($row))
            ->filter(fn (array $row): bool => $this->hasRowAnyValue($row))
            ->map(fn (array $row): Trading212ImportRowData => Trading212ImportRowData::fromHeaderMappedRow($row, $headerIndexMap))
            ->values();
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<string, int> normalized header label => first column index
     */
    private function buildHeaderIndexMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $label) {
            if (! array_key_exists($label, $map)) {
                $map[$label] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $headerIndexMap
     */
    private function validateRequiredHeaders(array $headerIndexMap): void
    {
        $missing = [];

        foreach (Trading212ImportRowData::REQUIRED_HEADERS as $required) {
            if (! array_key_exists($required, $headerIndexMap)) {
                $missing[] = $required;
            }
        }

        if ($missing === []) {
            return;
        }

        throw ValidationException::withMessages([
            'file' => ['The uploaded file is missing required columns: '.implode(', ', $missing).'.'],
        ]);
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
