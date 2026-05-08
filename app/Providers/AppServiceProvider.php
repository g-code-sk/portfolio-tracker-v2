<?php

namespace App\Providers;

use App\Models\Portfolio;
use App\Policies\PortfolioPolicy;
use App\Support\ApplicationConfig;
use Domain\Security\Contract\CurrentSecurityPriceProviderInterface;
use Domain\Security\Enums\SecurityDataProviderCode;
use Domain\Security\Service\FinnhubCurrentSecurityPriceProvider;
use Domain\Security\Service\YahooCurrentSecurityPriceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ApplicationConfig::class);

        $this->app->bind(CurrentSecurityPriceProviderInterface::class, function (Application $app): CurrentSecurityPriceProviderInterface {
            /** @var ApplicationConfig $applicationConfig */
            $applicationConfig = $app->make(ApplicationConfig::class);

            return match ($applicationConfig->getSecurityDataProviderCode()) {
                SecurityDataProviderCode::Finnhub => $app->make(FinnhubCurrentSecurityPriceProvider::class),
                SecurityDataProviderCode::Yahoo => $app->make(YahooCurrentSecurityPriceProvider::class),

                default => throw new RuntimeException(sprintf(
                    'Unsupported services.SECURITY_DATA_PROVIDER "%s". Use: finnhub, yahoo.',
                    $applicationConfig->getSecurityDataProvider()
                )),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Portfolio::class, PortfolioPolicy::class);
    }
}
