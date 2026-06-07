<?php

namespace Cynchro\Billing\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Cynchro\Billing\Models\Plan;

class PlanTest extends TestCase
{
    public function test_period_end_monthly(): void
    {
        $plan  = new Plan('p1', 'pro', 'Pro', 10.0, 'USD', 'month');
        $start = new DateTimeImmutable('2026-06-01 00:00:00');

        $this->assertSame('2026-07-01', $plan->periodEndFrom($start)->format('Y-m-d'));
    }

    public function test_period_end_yearly(): void
    {
        $plan  = new Plan('p1', 'pro', 'Pro', 100.0, 'USD', 'year');
        $start = new DateTimeImmutable('2026-06-01 00:00:00');

        $this->assertSame('2027-06-01', $plan->periodEndFrom($start)->format('Y-m-d'));
    }
}
