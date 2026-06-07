<?php

namespace Cynchro\Billing;

use PDO;

/**
 * Tablas propias del paquete: plans, plan_entitlements, subscriptions.
 * (La tabla `tenant_entitlements` es del base Modux y billing solo la escribe.)
 * El host las crea al instalar el módulo de billing.
 */
final class Schema
{
    public static function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS plans (
                id               CHAR(36)      NOT NULL PRIMARY KEY,
                `key`            VARCHAR(60)   NOT NULL,
                name             VARCHAR(120)  NOT NULL,
                price            DECIMAL(12,2) NOT NULL DEFAULT 0,
                currency         CHAR(3)       NOT NULL DEFAULT 'USD',
                `interval`       ENUM('month','year') NOT NULL DEFAULT 'month',
                gateway          VARCHAR(40)   NULL,
                gateway_price_id VARCHAR(120)  NULL,
                created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_key (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS plan_entitlements (
                id          BIGINT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                plan_id     CHAR(36)     NOT NULL,
                feature     VARCHAR(120) NOT NULL,
                type        ENUM('flag','quota','seat') NOT NULL,
                limit_value BIGINT       NULL,
                UNIQUE KEY uniq_plan_feature (plan_id, feature),
                FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS subscriptions (
                id                 CHAR(36)     NOT NULL PRIMARY KEY,
                tenant_id          CHAR(36)     NOT NULL,
                plan_id            CHAR(36)     NOT NULL,
                status             ENUM('trialing','active','past_due','canceled') NOT NULL DEFAULT 'active',
                gateway            VARCHAR(40)  NULL,
                external_id        VARCHAR(120) NULL,
                current_period_end DATETIME     NULL,
                cancel_at          DATETIME     NULL,
                created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_tenant (tenant_id),
                INDEX idx_external (external_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public static function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS subscriptions');
        $pdo->exec('DROP TABLE IF EXISTS plan_entitlements');
        $pdo->exec('DROP TABLE IF EXISTS plans');
    }
}
