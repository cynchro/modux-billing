<?php

namespace Cynchro\Billing\Gateway;

use DateTimeImmutable;

/**
 * Evento normalizado de la pasarela (resultado de parsear un webhook). Los
 * adaptadores (-stripe, -mercadopago) traducen su payload a esta forma común.
 */
final class GatewayEvent
{
    public const TYPE_ACTIVATED = 'subscription.activated';
    public const TYPE_RENEWED   = 'subscription.renewed';
    public const TYPE_CANCELED  = 'subscription.canceled';
    public const TYPE_PAST_DUE  = 'subscription.past_due';

    /** @param array<string, mixed> $raw */
    public function __construct(
        public readonly string $type,
        public readonly string $externalSubscriptionId,
        public readonly ?DateTimeImmutable $currentPeriodEnd = null,
        public readonly array $raw = []
    ) {
    }
}
