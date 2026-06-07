<?php

namespace Cynchro\Billing;

use PDO;
use DateTimeInterface;
use Cynchro\Billing\Contracts\EntitlementWriterInterface;

/**
 * Implementación por defecto del puerto de entitlements: escribe en la tabla
 * `tenant_entitlements` del base Modux (upsert por (tenant_id, feature)).
 * Es la ÚNICA costura billing → base; el base solo lee esa tabla.
 */
final class TenantEntitlementWriter implements EntitlementWriterInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function write(
        string $tenantId,
        array $entitlements,
        string $source,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tenant_entitlements
                (tenant_id, feature, type, limit_value, enabled, source, period_start, period_end)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                type         = VALUES(type),
                limit_value  = VALUES(limit_value),
                enabled      = 1,
                source       = VALUES(source),
                period_start = VALUES(period_start),
                period_end   = VALUES(period_end)'
        );

        $start = $periodStart->format('Y-m-d H:i:s');
        $end   = $periodEnd->format('Y-m-d H:i:s');

        foreach ($entitlements as $entitlement) {
            $stmt->execute([
                $tenantId,
                $entitlement->feature,
                $entitlement->type,
                $entitlement->limit,
                $source,
                $start,
                $end,
            ]);
        }
    }

    public function clear(string $tenantId, string $source): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM tenant_entitlements WHERE tenant_id = ? AND source = ?'
        );
        $stmt->execute([$tenantId, $source]);
    }
}
