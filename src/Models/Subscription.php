<?php

namespace Cynchro\Billing\Models;

use DateTimeImmutable;

final class Subscription
{
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELED = 'canceled';

    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $planId,
        public readonly string $status,
        public readonly ?string $gateway = null,
        public readonly ?string $externalId = null,
        public readonly ?DateTimeImmutable $currentPeriodEnd = null
    ) {
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_TRIALING, self::STATUS_ACTIVE], true);
    }
}
