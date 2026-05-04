<?php

namespace Tests\Unit\Support\LegacyMigration;

use App\Support\LegacyMigration\DryRunReport;
use CodeIgniter\Test\CIUnitTestCase;

final class DryRunReportTest extends CIUnitTestCase
{
    public function testEmptyReportIsGo(): void
    {
        $report = new DryRunReport('run-1', 99, 'site');
        $this->assertSame('GO', (string) ($report->toArray()['go_no_go_status'] ?? ''));
    }

    public function testWarningOnlyIsReview(): void
    {
        $report = new DryRunReport('run-1', 99, 'site');
        $report->addWarning('W001', 'warning');
        $this->assertSame('REVIEW', (string) ($report->toArray()['go_no_go_status'] ?? ''));
    }

    public function testQuarantineCandidateIsReview(): void
    {
        $report = new DryRunReport('run-1', 99, 'site');
        $report->addQuarantineCandidate('unit', 'daire_tanimlari', 10, 'missing unit no');
        $this->assertSame('REVIEW', (string) ($report->toArray()['go_no_go_status'] ?? ''));
    }

    public function testBlockerIsNoGo(): void
    {
        $report = new DryRunReport('run-1', 99, 'site');
        $report->addBlocker('B001', 'critical');
        $this->assertSame('NO_GO', (string) ($report->toArray()['go_no_go_status'] ?? ''));
    }
}

