<?php

namespace Domain\Shared\Validation;

use Illuminate\Validation\Rules\Password;

final class UserCredentialsRules
{
    /**
     * @return array<int, mixed>
     */
    public static function email(): array
    {
        return ['required', 'string', 'email', 'max:255'];
    }

    public static function password(): Password
    {
        return Password::min(8);
    }
}
