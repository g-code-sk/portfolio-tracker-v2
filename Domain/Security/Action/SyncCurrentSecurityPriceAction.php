<?php

namespace Domain\Security\Action;

use App\Models\Security;
use App\Models\SecurityDataProvider;
use Domain\Security\Contract\CurrentSecurityPriceProviderInterface;
use Domain\Security\Data\SyncCurrentSecurityPriceResultData;
use Throwable;

class SyncCurrentSecurityPriceAction
{
    public function __construct(
        private readonly CurrentSecurityPriceProviderInterface $currentSecurityPriceProvider
    ) {}

    public function execute(Security $security): SyncCurrentSecurityPriceResultData
    {
        if ($security->ticker === '') {
            return SyncCurrentSecurityPriceResultData::skipped($security->id, $security->ticker, 'Ticker is empty');
        }

        try {
            $currentPriceData = $this->currentSecurityPriceProvider->fetchCurrentPriceData(
                $security->ticker,
                $security->name,
                $security->isin,
            );

            if ($currentPriceData === null) {
                return SyncCurrentSecurityPriceResultData::skipped($security->id, $security->ticker, 'Current price data is null');
            }

            $securityDataProvider = SecurityDataProvider::query()
                ->where('code', $currentPriceData->providerCode->value)
                ->first();

            if ($securityDataProvider === null) {
                return SyncCurrentSecurityPriceResultData::failed(
                    $security->id,
                    $security->ticker,
                    sprintf('Security price provider not found for code: %s', $currentPriceData->providerCode->value),
                );
            }

            $security->current_price = $currentPriceData->price;
            $security->current_price_currency = $currentPriceData->currency;
            $security->current_price_updated_at = now();
            $security->current_data_provider_id = $securityDataProvider->id;

            $security->save();

            return SyncCurrentSecurityPriceResultData::updated($security->id, $security->ticker);
        } catch (Throwable $throwable) {
            return SyncCurrentSecurityPriceResultData::failed(
                $security->id,
                $security->ticker,
                $throwable->getMessage(),
            );
        }
    }
}
