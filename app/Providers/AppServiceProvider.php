<?php

namespace App\Providers;

use App\Models\Portfolio;
use App\Policies\PortfolioPolicy;
use App\Support\ApplicationConfig;
use Domain\Security\Contract\CurrentSecurityPriceProviderInterface;
use Domain\Security\Enums\SecurityDataProviderCode;
use Domain\Security\Service\FinnhubCurrentSecurityPriceProvider;
use Domain\Security\Service\YahooCurrentSecurityPriceProvider;
use Finnhub\Api\DefaultApi;
use Finnhub\Configuration;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ApplicationConfig::class);

        $this->app->singleton(DefaultApi::class, function (Application $app): DefaultApi {
            /** @var ApplicationConfig $applicationConfig */
            $applicationConfig = $app->make(ApplicationConfig::class);

            $finnhubConfiguration = Configuration::getDefaultConfiguration();
            $apiKey = $applicationConfig->getFinnhubApiKey();

            if ($apiKey !== null) {
                $finnhubConfiguration->setApiKey('token', $apiKey);
            }

            return new DefaultApi(new Client, $finnhubConfiguration);
        });

        $this->app->singleton(ApiClient::class, fn (): ApiClient => ApiClientFactory::createApiClient(
            clientOptions: [
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection' => 'keep-alive',
                ],
            ],
            retries: 3,
            retryDelay: 1000,
        ));

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
