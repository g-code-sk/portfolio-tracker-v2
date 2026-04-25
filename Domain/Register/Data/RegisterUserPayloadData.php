<?php

namespace Domain\Register\Data;

use Domain\Shared\Validation\UserCredentialsRules;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class RegisterUserPayloadData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $password_confirmation,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [...UserCredentialsRules::email(), Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', UserCredentialsRules::password()],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
