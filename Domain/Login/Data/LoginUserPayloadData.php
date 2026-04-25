<?php

namespace Domain\Login\Data;

use Domain\Shared\Validation\UserCredentialsRules;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LoginUserPayloadData extends Data
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'email' => UserCredentialsRules::email(),
            'password' => ['required', UserCredentialsRules::password()],
        ];
    }
}
