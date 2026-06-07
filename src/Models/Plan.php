<?php

namespace Cynchro\Billing\Models;

use DateTimeImmutable;

/**
 * Un plan comercial. `interval` define la duración del ciclo ('month'|'year'),
 * usada para calcular period_end cuando la pasarela no lo provee.
 */
final class Plan
{
    public function __construct(
        public readonly string $id,
        public readonly string $key,
        public readonly string $name,
        public readonly float $price,
        public readonly string $currency = 'USD',
        public readonly string $interval = 'month',
        public readonly ?string $gateway = null,
        public readonly ?string $gatewayPriceId = null
    ) {
    }

    /** Calcula el fin del ciclo desde un inicio según el intervalo del plan. */
    public function periodEndFrom(DateTimeImmutable $start): DateTimeImmutable
    {
        return $start->modify($this->interval === 'year' ? '+1 year' : '+1 month');
    }
}
