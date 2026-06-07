<?php

namespace Cynchro\Billing;

use PDO;
use Cynchro\Billing\Models\Plan;
use Cynchro\Billing\Models\PlanEntitlement;

class PlanRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Crea un plan y, opcionalmente, sus entitlements. */
    public function create(
        string $key,
        string $name,
        float $price = 0.0,
        string $currency = 'USD',
        string $interval = 'month',
        ?string $gateway = null,
        ?string $gatewayPriceId = null
    ): Plan {
        $id   = Uuid::v4();
        $stmt = $this->pdo->prepare(
            'INSERT INTO plans (id, `key`, name, price, currency, `interval`, gateway, gateway_price_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$id, $key, $name, $price, $currency, $interval, $gateway, $gatewayPriceId]);

        return new Plan($id, $key, $name, $price, $currency, $interval, $gateway, $gatewayPriceId);
    }

    public function addEntitlement(string $planId, PlanEntitlement $entitlement): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO plan_entitlements (plan_id, feature, type, limit_value)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE type = VALUES(type), limit_value = VALUES(limit_value)'
        );
        $stmt->execute([$planId, $entitlement->feature, $entitlement->type, $entitlement->limit]);
    }

    public function findByKey(string $key): ?Plan
    {
        $stmt = $this->pdo->prepare('SELECT * FROM plans WHERE `key` = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    /** @return list<PlanEntitlement> */
    public function entitlementsFor(string $planId): array
    {
        $stmt = $this->pdo->prepare('SELECT feature, type, limit_value FROM plan_entitlements WHERE plan_id = ?');
        $stmt->execute([$planId]);

        $out = [];
        foreach ((array) $stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[] = new PlanEntitlement(
                (string) $row['feature'],
                (string) $row['type'],
                $row['limit_value'] !== null ? (int) $row['limit_value'] : null
            );
        }

        return $out;
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Plan
    {
        return new Plan(
            (string) $row['id'],
            (string) $row['key'],
            (string) $row['name'],
            (float) $row['price'],
            (string) $row['currency'],
            (string) $row['interval'],
            $row['gateway'] !== null ? (string) $row['gateway'] : null,
            $row['gateway_price_id'] !== null ? (string) $row['gateway_price_id'] : null,
        );
    }
}
