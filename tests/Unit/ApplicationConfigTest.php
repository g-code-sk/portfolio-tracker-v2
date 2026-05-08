<?php

namespace Tests\Unit;

use App\Support\ApplicationConfig;
use Domain\Security\Enums\SecurityDataProviderCode;
use Tests\TestCase;

class ApplicationConfigTest extends TestCase
{
    public function test_get_finnhub_api_key_returns_null_for_empty_string(): void
    {
        config(['services.finnhub.key' => '']);

        $this->assertNull($this->app->make(ApplicationConfig::class)->getFinnhubApiKey());
    }

    public function test_get_finnhub_api_key_returns_string_when_set(): void
    {
        config(['services.finnhub.key' => 'abc']);

        $this->assertSame('abc', $this->app->make(ApplicationConfig::class)->getFinnhubApiKey());
    }

    public function test_get_security_price_refresh_after_hours_clamps_negative_to_zero(): void
    {
        config(['services.security_price_refresh_after_hours' => -5]);

        $this->assertSame(0, $this->app->make(ApplicationConfig::class)->getSecurityPriceRefreshAfterHours());
    }

    public function test_get_normalized_security_data_provider_driver_trims_and_lowercases(): void
    {
        config(['services.SECURITY_DATA_PROVIDER' => '  Yahoo ']);

        $this->assertSame('yahoo', $this->app->make(ApplicationConfig::class)->getNormalizedSecurityDataProviderDriver());
    }

    public function test_get_security_data_provider_returns_raw_config_string(): void
    {
        config(['services.SECURITY_DATA_PROVIDER' => '  Yahoo ']);

        $this->assertSame('  Yahoo ', $this->app->make(ApplicationConfig::class)->getSecurityDataProvider());
    }

    public function test_get_security_data_provider_code_returns_enum_for_normalized_values(): void
    {
        config(['services.SECURITY_DATA_PROVIDER' => '  FINNHUB ']);

        $this->assertSame(
            SecurityDataProviderCode::Finnhub,
            $this->app->make(ApplicationConfig::class)->getSecurityDataProviderCode()
        );
    }

    public function test_get_security_data_provider_code_returns_null_for_unknown_driver(): void
    {
        config(['services.SECURITY_DATA_PROVIDER' => 'unknown']);

        $this->assertNull($this->app->make(ApplicationConfig::class)->getSecurityDataProviderCode());
    }

    public function test_get_session_lifetime_minutes_uses_session_config(): void
    {
        config(['session.lifetime' => 60]);

        $this->assertSame(60, $this->app->make(ApplicationConfig::class)->getSessionLifetimeMinutes());
    }

    public function test_get_app_name_uses_app_config(): void
    {
        config(['app.name' => 'Portfolio']);

        $this->assertSame('Portfolio', $this->app->make(ApplicationConfig::class)->getAppName());
    }
}
