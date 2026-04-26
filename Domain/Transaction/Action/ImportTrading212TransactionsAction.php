<?php

namespace Domain\Transaction\Action;

use App\Models\Portfolio;

class ImportTrading212TransactionsAction
{
    public function execute(Portfolio $portfolio, string $storedFilePath): void
    {
        // Todo 1 only wires controller delegation.
        // Trading 212 parsing/persistence is implemented in following todos.
    }
}
