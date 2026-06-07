<?php

namespace Cynchro\Billing;

use DateTimeImmutable;
use Cynchro\Billing\Models\Subscription;
use Cynchro\Billing\Gateway\GatewayEvent;
use Cynchro\Billing\Contracts\EntitlementWriterInterface;

/**
 * Orquesta la vida de la suscripción y, en cada cambio, proyecta los
 * entitlements del plan sobre el tenant (la costura billing → base).
 *
 * Regla: billing es el único que escribe `tenant_entitlements`; el base lee.
 */
final class BillingManager
{
    public function __construct(
        private PlanRepository $plans,
        private SubscriptionRepository $subscriptions,
        private EntitlementWriterInterface $entitlements
    ) {
    }

    /**
     * Alta/activación de un plan para un tenant (p. ej. tras un checkout exitoso).
     * Escribe los entitlements del plan con el período del ciclo.
     */
    public function subscribe(
        string $tenantId,
        string $planKey,
        ?string $gateway = null,
        ?string $externalId = null,
        ?DateTimeImmutable $periodEnd = null
    ): Subscription {
        $plan = $this->plans->findByKey($planKey);
        if ($plan === null) {
            throw new BillingException("Plan [{$planKey}] not found.");
        }

        $start = new DateTimeImmutable();
        $end   = $periodEnd ?? $plan->periodEndFrom($start);

        $subscription = new Subscription(
            Uuid::v4(),
            $tenantId,
            $plan->id,
            Subscription::STATUS_ACTIVE,
            $gateway ?? $plan->gateway,
            $externalId,
            $end
        );
        $this->subscriptions->save($subscription);

        $this->entitlements->write(
            $tenantId,
            $this->plans->entitlementsFor($plan->id),
            $this->source($subscription->gateway),
            $start,
            $end
        );

        return $subscription;
    }

    /**
     * Procesa un evento normalizado de la pasarela (webhook ya verificado con el
     * WebhookVerifier del base). Renueva, cancela o marca past_due.
     */
    public function handleEvent(GatewayEvent $event): void
    {
        $subscription = $this->subscriptions->findByExternalId($event->externalSubscriptionId);
        if ($subscription === null) {
            throw new BillingException("Subscription [{$event->externalSubscriptionId}] not found.");
        }

        match ($event->type) {
            GatewayEvent::TYPE_ACTIVATED,
            GatewayEvent::TYPE_RENEWED  => $this->renew($subscription, $event->currentPeriodEnd),
            GatewayEvent::TYPE_CANCELED => $this->cancel($subscription),
            GatewayEvent::TYPE_PAST_DUE => $this->subscriptions->updateStatusAndPeriod(
                $subscription->id,
                Subscription::STATUS_PAST_DUE,
                $subscription->currentPeriodEnd
            ),
            default => null,
        };
    }

    private function renew(Subscription $subscription, ?DateTimeImmutable $newPeriodEnd): void
    {
        $start = new DateTimeImmutable();
        $end   = $newPeriodEnd ?? $start->modify('+1 month');

        $this->subscriptions->updateStatusAndPeriod($subscription->id, Subscription::STATUS_ACTIVE, $end);

        $this->entitlements->write(
            $subscription->tenantId,
            $this->plans->entitlementsFor($subscription->planId),
            $this->source($subscription->gateway),
            $start,
            $end
        );
    }

    private function cancel(Subscription $subscription): void
    {
        $this->subscriptions->updateStatusAndPeriod(
            $subscription->id,
            Subscription::STATUS_CANCELED,
            $subscription->currentPeriodEnd
        );
        $this->entitlements->clear($subscription->tenantId, $this->source($subscription->gateway));
    }

    private function source(?string $gateway): string
    {
        return 'billing:' . ($gateway ?? 'manual');
    }
}
