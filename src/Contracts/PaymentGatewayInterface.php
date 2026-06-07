<?php

namespace Cynchro\Billing\Contracts;

use Cynchro\Billing\Models\Plan;
use Cynchro\Billing\Gateway\GatewayEvent;
use Cynchro\Billing\Gateway\CheckoutSession;

/**
 * Adaptador de pasarela de pago. Implementaciones: cynchro/modux-billing-stripe,
 * cynchro/modux-billing-mercadopago. El core de billing es agnóstico de pasarela.
 */
interface PaymentGatewayInterface
{
    /** Identificador corto de la pasarela ('stripe', 'mercadopago'). */
    public function name(): string;

    /** Inicia un checkout para que el tenant contrate el plan. */
    public function createCheckout(string $tenantId, Plan $plan): CheckoutSession;

    /**
     * Traduce un webhook entrante de la pasarela a un GatewayEvent normalizado.
     * La verificación de firma se hace antes con el WebhookVerifier del base.
     *
     * @param array<string, mixed> $payload
     */
    public function parseWebhook(array $payload): GatewayEvent;

    /** Cancela la suscripción en la pasarela. */
    public function cancel(string $externalSubscriptionId): void;
}
