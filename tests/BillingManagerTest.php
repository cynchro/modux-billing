<?php

namespace Cynchro\Billing\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Cynchro\Billing\BillingManager;
use Cynchro\Billing\BillingException;
use Cynchro\Billing\PlanRepository;
use Cynchro\Billing\SubscriptionRepository;
use Cynchro\Billing\Models\Plan;
use Cynchro\Billing\Models\PlanEntitlement;
use Cynchro\Billing\Models\Subscription;
use Cynchro\Billing\Gateway\GatewayEvent;
use Cynchro\Billing\Contracts\EntitlementWriterInterface;

class BillingManagerTest extends TestCase
{
    private function plans(): PlanRepository
    {
        $plans = $this->createMock(PlanRepository::class);
        $plans->method('findByKey')->willReturnCallback(
            fn (string $key): ?Plan => $key === 'pro'
                ? new Plan('p1', 'pro', 'Pro', 10.0, 'USD', 'month', 'stripe')
                : null
        );
        $plans->method('entitlementsFor')->willReturn([
            new PlanEntitlement('ia.rag', 'flag'),
            new PlanEntitlement('api.calls', 'quota', 1000),
        ]);
        return $plans;
    }

    public function test_subscribe_writes_plan_entitlements_to_tenant(): void
    {
        $subs = $this->createMock(SubscriptionRepository::class);
        $subs->expects($this->once())->method('save');

        $writer = $this->createMock(EntitlementWriterInterface::class);
        $writer->expects($this->once())
            ->method('write')
            ->with(
                't1',
                $this->callback(fn (array $ents): bool => count($ents) === 2),
                'billing:stripe',
                $this->isInstanceOf(\DateTimeInterface::class),
                $this->isInstanceOf(\DateTimeInterface::class),
            );

        $manager = new BillingManager($this->plans(), $subs, $writer);
        $sub     = $manager->subscribe('t1', 'pro');

        $this->assertSame('t1', $sub->tenantId);
        $this->assertSame('stripe', $sub->gateway);
        $this->assertTrue($sub->isActive());
        $this->assertInstanceOf(DateTimeImmutable::class, $sub->currentPeriodEnd);
    }

    public function test_subscribe_unknown_plan_throws(): void
    {
        $manager = new BillingManager(
            $this->plans(),
            $this->createMock(SubscriptionRepository::class),
            $this->createMock(EntitlementWriterInterface::class)
        );

        $this->expectException(BillingException::class);
        $manager->subscribe('t1', 'no-existe');
    }

    public function test_renewed_event_rewrites_entitlements_and_moves_period(): void
    {
        $subs = $this->createMock(SubscriptionRepository::class);
        $subs->method('findByExternalId')->willReturn(
            new Subscription('s1', 't1', 'p1', 'active', 'stripe', 'ext1')
        );
        $subs->expects($this->once())
            ->method('updateStatusAndPeriod')
            ->with('s1', Subscription::STATUS_ACTIVE, $this->isInstanceOf(\DateTimeInterface::class));

        $writer = $this->createMock(EntitlementWriterInterface::class);
        $writer->expects($this->once())->method('write')
            ->with('t1', $this->anything(), 'billing:stripe', $this->anything(), $this->anything());

        $manager = new BillingManager($this->plans(), $subs, $writer);
        $manager->handleEvent(new GatewayEvent(GatewayEvent::TYPE_RENEWED, 'ext1', new DateTimeImmutable('+1 month')));
    }

    public function test_canceled_event_clears_entitlements(): void
    {
        $subs = $this->createMock(SubscriptionRepository::class);
        $subs->method('findByExternalId')->willReturn(
            new Subscription('s1', 't1', 'p1', 'active', 'stripe', 'ext1')
        );
        $subs->expects($this->once())
            ->method('updateStatusAndPeriod')
            ->with('s1', Subscription::STATUS_CANCELED, $this->anything());

        $writer = $this->createMock(EntitlementWriterInterface::class);
        $writer->expects($this->once())->method('clear')->with('t1', 'billing:stripe');
        $writer->expects($this->never())->method('write');

        $manager = new BillingManager($this->plans(), $subs, $writer);
        $manager->handleEvent(new GatewayEvent(GatewayEvent::TYPE_CANCELED, 'ext1'));
    }

    public function test_event_for_unknown_subscription_throws(): void
    {
        $subs = $this->createMock(SubscriptionRepository::class);
        $subs->method('findByExternalId')->willReturn(null);

        $manager = new BillingManager(
            $this->plans(),
            $subs,
            $this->createMock(EntitlementWriterInterface::class)
        );

        $this->expectException(BillingException::class);
        $manager->handleEvent(new GatewayEvent(GatewayEvent::TYPE_RENEWED, 'nope'));
    }
}
