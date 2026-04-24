<?php

namespace Domain\Register\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class RegisterUserResponseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {
    }
}
