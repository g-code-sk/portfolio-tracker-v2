<?php

namespace Domain\Auth\Data;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AuthSessionData extends Data
{
    public function __construct(
        public bool $isActive,
        public int $lifetimeMinutes,
        public ?string $expiresAt,
        public ?string $sessionId,
    ) {}

    public static function active(Request $request): self
    {
        $lifetimeMinutes = (int) config('session.lifetime', 120);

        return new self(
            isActive: true,
            lifetimeMinutes: $lifetimeMinutes,
            expiresAt: now()->addMinutes($lifetimeMinutes)->toIso8601String(),
            sessionId: $request->hasSession() ? $request->session()->getId() : null,
        );
    }

    public static function ended(): self
    {
        return new self(
            isActive: false,
            lifetimeMinutes: (int) config('session.lifetime', 120),
            expiresAt: null,
            sessionId: null,
        );
    }
}
