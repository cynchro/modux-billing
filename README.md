# cynchro/modux-billing

SDK de billing para SaaS multi-tenant: **planes, suscripciones y entitlements**.
Agnóstico de pasarela (Stripe, Mercado Pago) y framework-agnóstico (recibe un `PDO`).

Es el módulo opcional de la capa comercial de [Modux](https://github.com/cynchro/modux).
Diseño completo: `docs/adr/0001-saas-identity-entitlements-billing.md` del framework.

## Idea central

> **Billing escribe, el base solo lee.**

Cuando un tenant contrata o renueva un plan, billing proyecta los entitlements del
plan sobre la tabla `tenant_entitlements` del host (con el período del ciclo). El
framework base hace el gating (feature flags, cuotas) leyendo esa tabla, **sin conocer
billing**. Así los módulos de producto (p. ej. `modux-ia`) nunca dependen de billing.

```
contratar plan ──► BillingManager.subscribe() ──► tenant_entitlements (source=billing:*)
webhook pasarela ─► BillingManager.handleEvent() ─► renueva / cancela → reescribe / limpia
```

## Instalación

```bash
composer require cynchro/modux-billing
```

Requiere PHP ≥ 8.2, `ext-pdo`, `ext-json`.

## Uso

```php
use Cynchro\Billing\{Schema, PlanRepository, SubscriptionRepository,
    TenantEntitlementWriter, BillingManager};
use Cynchro\Billing\Models\PlanEntitlement;

Schema::up($pdo); // crea plans, plan_entitlements, subscriptions

$plans = new PlanRepository($pdo);
$pro   = $plans->create('pro', 'Pro', 29.0, 'USD', 'month', 'stripe');
$plans->addEntitlement($pro->id, new PlanEntitlement('ia.rag', 'flag'));
$plans->addEntitlement($pro->id, new PlanEntitlement('api.calls', 'quota', 1000));

$billing = new BillingManager(
    $plans,
    new SubscriptionRepository($pdo),
    new TenantEntitlementWriter($pdo) // escribe tenant_entitlements del base
);

// Tras un checkout exitoso:
$billing->subscribe($tenantId, 'pro', 'stripe', $externalSubscriptionId);

// Desde un webhook de la pasarela (ya verificado con el WebhookVerifier del base):
$billing->handleEvent($gateway->parseWebhook($payload));
```

## Pasarelas

El core es agnóstico: las pasarelas implementan `PaymentGatewayInterface`.

- `cynchro/modux-billing-stripe`
- `cynchro/modux-billing-mercadopago`

## Calidad

```bash
composer test      # PHPUnit
composer analyse   # PHPStan level 6
composer lint      # PSR-12
```

MIT.
