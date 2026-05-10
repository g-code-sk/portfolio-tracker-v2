<?php

namespace Domain\Security\Data;

use Carbon\CarbonImmutable;

final readonly class FinnhubQuoteData
{
    private function __construct(
        public mixed $currentPrice,
        public mixed $quotedAtUnix,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            currentPrice: data_get($payload, 'c'),
            quotedAtUnix: data_get($payload, 't'),
        );
    }

    public function hasPrice(): bool
    {
        return is_numeric($this->currentPrice);
    }

    public function isEmpty(): bool
    {
        if (! $this->hasPrice()) {
            return true;
        }

        $priceFloat = (float) $this->currentPrice;
        $hasUsableQuoteTimestamp = is_numeric($this->quotedAtUnix) && (int) $this->quotedAtUnix !== 0;

        return $priceFloat === 0.0 && ! $hasUsableQuoteTimestamp;
    }

    public function getQuotedAt(): CarbonImmutable
    {
        if (is_numeric($this->quotedAtUnix) && (int) $this->quotedAtUnix > 0) {
            return CarbonImmutable::createFromTimestampUTC((int) $this->quotedAtUnix);
        }

        return CarbonImmutable::now('UTC');
    }

    public function getFormattedCurrentPrice(): ?string
    {
        if (! $this->hasPrice()) {
            return null;
        }

        return number_format((float) $this->currentPrice, 10, '.', '');
    }
}
