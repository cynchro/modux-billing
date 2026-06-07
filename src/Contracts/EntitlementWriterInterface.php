<?php

namespace Cynchro\Billing\Contracts;

use DateTimeInterface;
use Cynchro\Billing\Models\PlanEntitlement;

/**
 * Puerto hacia los entitlements del host. La implementación por defecto
 * (TenantEntitlementWriter) escribe en la tabla `tenant_entitlements` del base
 * Modux; otra app puede proveer la suya. Es la única costura billing → base:
 * billing ESCRIBE, el base solo LEE.
 */
interface EntitlementWriterInterface
{
    /**
     * Proyecta los entitlements de un plan sobre un tenant (upsert por feature),
     * con el período del ciclo. `source` marca el origen ('billing:stripe').
     *
     * @param list<PlanEntitlement> $entitlements
     */
    public function write(
        string $tenantId,
        array $entitlements,
        string $source,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd
    ): void;

    /** Quita los entitlements de un origen (p. ej. al cancelar la suscripción). */
    public function clear(string $tenantId, string $source): void;
}
