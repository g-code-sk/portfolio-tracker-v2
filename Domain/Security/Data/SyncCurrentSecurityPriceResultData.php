<?php

namespace Domain\Security\Data;

final readonly class SyncCurrentSecurityPriceResultData
{
    public function __construct(
        public int $securityId,
        public string $ticker,
        public bool $isUpdated,
        public bool $isSkipped,
        public bool $hasFailed,
        public ?string $failureMessage = null,
    ) {}

    public static function skipped(int $securityId, string $ticker, ?string $failureMessage = null): self
    {
        return new self(
            securityId: $securityId,
            ticker: $ticker,
            isUpdated: false,
            isSkipped: true,
            hasFailed: false,
            failureMessage: $failureMessage,
        );
    }

    public static function updated(int $securityId, string $ticker, ?string $failureMessage = null): self
    {
        return new self(
            securityId: $securityId,
            ticker: $ticker,
            isUpdated: true,
            isSkipped: false,
            hasFailed: false,
            failureMessage: $failureMessage,
        );
    }

    public static function failed(int $securityId, string $ticker, string $failureMessage): self
    {
        return new self(
            securityId: $securityId,
            ticker: $ticker,
            isUpdated: false,
            isSkipped: false,
            hasFailed: true,
            failureMessage: $failureMessage,
        );
    }
}
