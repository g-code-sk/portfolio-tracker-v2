<?php

namespace Database\Seeders;

use App\Enums\SecurityTypeCode;
use App\Models\SecurityType;
use Illuminate\Database\Seeder;

class SecurityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SecurityType::query()->updateOrInsert(
            ['code' => SecurityTypeCode::Buy->value],
            ['name' => 'Buy'],
        );

        SecurityType::query()->updateOrInsert(
            ['code' => SecurityTypeCode::Sell->value],
            ['name' => 'Sell'],
        );
    }
}
