<?php

namespace Domain\Auth\Data;

use Domain\Login\Data\LoginUserResponseData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AuthUserSessionResponseData extends Data
{
    public function __construct(
        public ?LoginUserResponseData $user,
        public AuthSessionData $session,
    ) {}
}
