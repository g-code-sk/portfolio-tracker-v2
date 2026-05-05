<?php

namespace Database\Seeders;

use App\Models\TransactionType;
use Domain\Transaction\Enums\TransactionTypeCode;
use Illuminate\Database\Seeder;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TransactionType::query()->updateOrInsert(
            ['code' => TransactionTypeCode::Buy->value],
            ['name' => 'Buy'],
        );

        TransactionType::query()->updateOrInsert(
            ['code' => TransactionTypeCode::Sell->value],
            ['name' => 'Sell'],
        );
    }
}
