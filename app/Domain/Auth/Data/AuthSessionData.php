<?php

namespace Domain\Auth\Data;

use App\Support\ApplicationConfig;
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
        $lifetimeMinutes = app(ApplicationConfig::class)->getSessionLifetimeMinutes();

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
            lifetimeMinutes: app(ApplicationConfig::class)->getSessionLifetimeMinutes(),
            expiresAt: null,
            sessionId: null,
        );
    }
}
