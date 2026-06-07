<?php

namespace Cynchro\Billing\Gateway;

/** Resultado de iniciar un checkout en la pasarela. */
final class CheckoutSession
{
    public function __construct(
        public readonly string $url,
        public readonly ?string $externalId = null
    ) {
    }
}
