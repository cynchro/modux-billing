<?php

namespace Cynchro\Billing;

use PDO;
use DateTimeImmutable;
use Cynchro\Billing\Models\Subscription;

class SubscriptionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(Subscription $sub): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO subscriptions
                (id, tenant_id, plan_id, status, gateway, external_id, current_period_end, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $sub->id,
            $sub->tenantId,
            $sub->planId,
            $sub->status,
            $sub->gateway,
            $sub->externalId,
            $sub->currentPeriodEnd?->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByExternalId(string $externalId): ?Subscription
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subscriptions WHERE external_id = ? LIMIT 1');
        $stmt->execute([$externalId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findActiveByTenant(string $tenantId): ?Subscription
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM subscriptions
             WHERE tenant_id = ? AND status IN ('trialing','active')
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function updateStatusAndPeriod(string $id, string $status, ?DateTimeImmutable $periodEnd): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE subscriptions SET status = ?, current_period_end = ? WHERE id = ?'
        );
        $stmt->execute([$status, $periodEnd?->format('Y-m-d H:i:s'), $id]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Subscription
    {
        return new Subscription(
            (string) $row['id'],
            (string) $row['tenant_id'],
            (string) $row['plan_id'],
            (string) $row['status'],
            $row['gateway'] !== null ? (string) $row['gateway'] : null,
            $row['external_id'] !== null ? (string) $row['external_id'] : null,
            $row['current_period_end'] !== null ? new DateTimeImmutable((string) $row['current_period_end']) : null,
        );
    }
}
