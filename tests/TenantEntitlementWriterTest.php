<?php

namespace Cynchro\Billing\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Cynchro\Billing\TenantEntitlementWriter;
use Cynchro\Billing\Models\PlanEntitlement;

class TenantEntitlementWriterTest extends TestCase
{
    public function test_write_upserts_each_entitlement(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->exactly(2))->method('execute')->willReturn(true);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $writer = new TenantEntitlementWriter($pdo);
        $writer->write(
            't1',
            [new PlanEntitlement('ia.rag', 'flag'), new PlanEntitlement('api.calls', 'quota', 1000)],
            'billing:stripe',
            new DateTimeImmutable('2026-06-01'),
            new DateTimeImmutable('2026-07-01'),
        );
    }

    public function test_clear_deletes_by_source(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['t1', 'billing:stripe'])
            ->willReturn(true);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        (new TenantEntitlementWriter($pdo))->clear('t1', 'billing:stripe');
    }
}
