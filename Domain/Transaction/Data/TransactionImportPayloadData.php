<?php

namespace Domain\Transaction\Data;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Domain\Transaction\Enums\TransactionImportType;

#[TypeScript]
class TransactionImportPayloadData extends Data
{
    public function __construct(
        public string $import_type,
        public UploadedFile $file,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'import_type' => ['required', new Enum(TransactionImportType::class)],
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx'],
        ];
    }
}
