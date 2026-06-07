<?php

namespace Cynchro\Billing\Models;

/**
 * Lo que un plan otorga sobre una feature. Se proyecta a `tenant_entitlements`
 * del base al activar/renovar la suscripción. `limit` null = ilimitado.
 */
final class PlanEntitlement
{
    public function __construct(
        public readonly string $feature,
        public readonly string $type,   // 'flag' | 'quota' | 'seat'
        public readonly ?int $limit = null
    ) {
    }
}
