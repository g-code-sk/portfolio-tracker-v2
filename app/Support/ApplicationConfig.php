<?php

namespace App\Support;

use Domain\Security\Enums\SecurityDataProviderCode;

final class ApplicationConfig
{
    public function getFinnhubApiKey(): ?string
    {
        $key = config('services.finnhub.key');

        if (! is_string($key) || $key === '') {
            return null;
        }

        return $key;
    }

    public function getSecurityPriceRefreshAfterHours(): int
    {
        return max(0, (int) config('services.security_price_refresh_after_hours', 24));
    }

    public function getSecurityDataProvider(): string
    {
        return (string) config('services.SECURITY_DATA_PROVIDER', 'finnhub');
    }

    public function getNormalizedSecurityDataProviderDriver(): string
    {
        return strtolower(trim($this->getSecurityDataProvider()));
    }

    public function getSecurityDataProviderCode(): ?SecurityDataProviderCode
    {
        return SecurityDataProviderCode::tryFrom($this->getNormalizedSecurityDataProviderDriver());
    }

    public function getSessionLifetimeMinutes(): int
    {
        return (int) config('session.lifetime', 120);
    }

    public function getAppName(): string
    {
        return (string) config('app.name', 'Laravel');
    }
}
