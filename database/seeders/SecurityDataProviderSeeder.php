<?php

namespace Database\Seeders;

use App\Models\SecurityDataProvider;
use Domain\Security\Enums\SecurityDataProviderCode;
use Illuminate\Database\Seeder;

class SecurityDataProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SecurityDataProvider::query()->updateOrCreate(
            [
                'code' => SecurityDataProviderCode::Yahoo->value,
            ],
            [
                'name' => 'Yahoo Finance',
            ],
        );

        SecurityDataProvider::query()->updateOrCreate(
            [
                'code' => SecurityDataProviderCode::Finnhub->value,
            ],
            [
                'name' => 'Finnhub',
            ],
        );
    }
}
