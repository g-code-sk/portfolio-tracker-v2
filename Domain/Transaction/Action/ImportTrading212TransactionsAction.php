<?php

namespace Domain\Transaction\Action;

use App\Models\Portfolio;

class ImportTrading212TransactionsAction
{
    public function __construct(
        private readonly ParseTrading212CsvAction $parseTrading212Csv
    ) {}

    public function execute(Portfolio $portfolio, string $storedFilePath): void
    {
        $importRows = $this->parseTrading212Csv->execute($storedFilePath);

        // Todo 2 only parses and validates Trading 212 CSV shape.
        // Persistence logic is implemented in following todos.
    }
}
