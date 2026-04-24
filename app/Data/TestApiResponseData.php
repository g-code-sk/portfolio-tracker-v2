<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TestApiResponseData extends Data
{
    /**
     * @param array<int, TestItemData> $items
     */
    public function __construct(
        public string $status,
        public string $message,
        public string $timestamp,
        #[DataCollectionOf(TestItemData::class)]
        public array $items,
    ) {
    }
}
