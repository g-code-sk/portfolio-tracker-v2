<?php

namespace Domain\Security\Service;

use Domain\Security\Data\FinnhubSymbolSearchHitData;

class FinnhubSymbolSearchHitMatcher
{
    /**
     * @param  list<FinnhubSymbolSearchHitData>  $hits
     * @return list<string>
     */
    public function matchSymbolsFromHits(array $hits, string $tickerUpper, ?string $normalizedIsin, ?string $normalizedName): array
    {
        $passedHit = [];

        foreach ($hits as $hit) {
            if (! $hit->isEquityLikeInstrumentType()) {
                continue;
            }

            if (! $hit->matchesTicker($tickerUpper)) {
                continue;
            }

            if (! $hit->matchesIsin($normalizedIsin)) {
                continue;
            }

            $passedHit[] = $hit;
        }

        if (count($passedHit) > 1 && $normalizedName !== null && $normalizedName !== '') {
            $passedHit = array_values(array_filter(
                $passedHit,
                fn (FinnhubSymbolSearchHitData $hit): bool => $hit->matchesNormalizedNameTokens($normalizedName),
            ));
        }

        /** @var array<string, true> $unique */
        $unique = [];

        foreach ($passedHit as $hit) {
            $unique[$hit->symbol] = true;
        }

        return array_keys($unique);
    }
}
