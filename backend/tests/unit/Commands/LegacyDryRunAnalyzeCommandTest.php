<?php

namespace Tests\Unit\Commands;

use App\Commands\LegacyDryRunAnalyzeCommand;
use App\Support\LegacyMigration\Contracts\LegacyScopeValidatorInterface;
use App\Support\LegacyMigration\DryRunReport;
use CodeIgniter\Test\CIUnitTestCase;

final class LegacyDryRunAnalyzeCommandTest extends CIUnitTestCase
{
    public function testParseOptionsSupportsEqualsSyntax(): void
    {
        $command = new LegacyDryRunAnalyzeCommand(service('logger'), service('commands'));
        $options = $command->parseOptions([
            '--run-id=legacy-dryrun-001',
            '--company-id=1',
            '--legacy-connection=legacy',
            '--scope=all',
            '--limit=0',
            '--write-quarantine=no',
            '--format=json',
        ]);

        $this->assertSame('legacy-dryrun-001', $options['run-id'] ?? null);
        $this->assertSame('1', $options['company-id'] ?? null);
        $this->assertSame('legacy', $options['legacy-connection'] ?? null);
        $this->assertSame('all', $options['scope'] ?? null);
        $this->assertSame('0', $options['limit'] ?? null);
        $this->assertSame('no', $options['write-quarantine'] ?? null);
        $this->assertSame('json', $options['format'] ?? null);
    }

    public function testParseOptionsSupportsSpaceSeparatedSyntax(): void
    {
        $command = new LegacyDryRunAnalyzeCommand(service('logger'), service('commands'));
        $options = $command->parseOptions([
            '--run-id', 'legacy-dryrun-001',
            '--company-id', '1',
            '--legacy-connection', 'legacy',
            '--scope', 'all',
            '--limit', '0',
            '--write-quarantine', 'no',
            '--format', 'json',
        ]);

        $this->assertSame('legacy-dryrun-001', $options['run-id'] ?? null);
        $this->assertSame('1', $options['company-id'] ?? null);
        $this->assertSame('legacy', $options['legacy-connection'] ?? null);
        $this->assertSame('all', $options['scope'] ?? null);
        $this->assertSame('0', $options['limit'] ?? null);
        $this->assertSame('no', $options['write-quarantine'] ?? null);
        $this->assertSame('json', $options['format'] ?? null);
    }

    public function testParseOptionsFallsBackToArgvWhenParamsDoNotContainOptions(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            /** @return list<string> */
            protected function readArgvOptionTokens(): array
            {
                return [
                    'spark',
                    'legacy:dry-run-analyze',
                    '--run-id', 'legacy-dryrun-001',
                    '--company-id', '1',
                    '--legacy-connection', 'legacy',
                    '--scope', 'all',
                    '--limit', '0',
                    '--write-quarantine', 'no',
                    '--format', 'json',
                ];
            }
        };

        // Simulates CI4 runtime case where run(array $params) is not raw option tokens.
        $options = $command->parseOptions(['legacy:dry-run-analyze']);
        $this->assertSame('legacy-dryrun-001', $options['run-id'] ?? null);
        $this->assertSame('1', $options['company-id'] ?? null);
        $this->assertSame('legacy', $options['legacy-connection'] ?? null);
        $this->assertSame('all', $options['scope'] ?? null);
        $this->assertSame('0', $options['limit'] ?? null);
        $this->assertSame('no', $options['write-quarantine'] ?? null);
        $this->assertSame('json', $options['format'] ?? null);
    }

    public function testMissingRequiredOptionsFails(): void
    {
        $command = new LegacyDryRunAnalyzeCommand(service('logger'), service('commands'));
        $exitCode = $command->run(['--run-id=test-run']);
        $this->assertSame(1, $exitCode);
    }

    public function testValidRequiredOptionsReturnsDesignOnlyMessage(): void
    {
        $command = new LegacyDryRunAnalyzeCommand(service('logger'), service('commands'));
        $exitCode = $command->run([
            '--run-id=test-run',
            '--company-id=12',
            '--legacy-connection=legacy',
            '--scope=request',
            '--limit=100',
            '--write-quarantine=no',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('NOT IMPLEMENTED: analyzer validation design only', $command->designOnlyMessage());
    }

    public function testNoImportSideEffects(): void
    {
        $command = new LegacyDryRunAnalyzeCommand(service('logger'), service('commands'));
        $exitCode = $command->run([
            '--run-id=test-run',
            '--company-id=12',
            '--legacy-connection=legacy',
            '--scope=request',
            '--limit=10',
            '--write-quarantine=no',
        ]);

        // Skeleton only: no import/data write path exists.
        $this->assertSame(0, $exitCode);
    }

    public function testValidJsonOutputContainsContractFields(): void
    {
        $command = new LegacyDryRunAnalyzeCommand(service('logger'), service('commands'));
        $report = $command->buildDesignOnlyReport([
            'run-id' => 'json-run',
            'company-id' => '12',
            'legacy-connection' => 'legacy',
            'scope' => 'site',
            'limit' => '50',
            'write-quarantine' => 'no',
            'format' => 'json',
        ]);
        $json = $command->formatReportOutput($report, 'json');
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('json-run', (string) ($decoded['run_id'] ?? ''));
        $this->assertSame(12, (int) ($decoded['company_id'] ?? 0));
        $this->assertSame('site', (string) ($decoded['scope'] ?? ''));
        $this->assertArrayHasKey('go_no_go_status', $decoded);
    }

    public function testDefaultTextOutputWorks(): void
    {
        $command = new LegacyDryRunAnalyzeCommand(service('logger'), service('commands'));
        $report = $command->buildDesignOnlyReport([
            'run-id' => 'txt-run',
            'company-id' => '7',
            'legacy-connection' => 'legacy',
            'scope' => 'all',
            'limit' => '100',
            'write-quarantine' => 'no',
        ]);
        $text = $command->formatReportOutput($report, 'text');
        $this->assertStringContainsString('run_id=txt-run', $text);
        $this->assertStringContainsString('go_no_go_status=REVIEW', $text);
    }

    public function testCommandRoutesSiteScopeToValidator(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            public bool $called = false;
            protected function makeSiteValidator(): LegacyScopeValidatorInterface
            {
                return new class($this) implements LegacyScopeValidatorInterface {
                    public function __construct(private readonly object $parent) {}
                    public function validate(DryRunReport $report, array $options): DryRunReport
                    {
                        $this->parent->called = true;
                        $report->setSourceCount('blok_tanimlari', 1);
                        return $report;
                    }
                };
            }
        };

        $exitCode = $command->run([
            '--run-id=route-run',
            '--company-id=12',
            '--legacy-connection=legacy',
            '--scope=site',
            '--limit=10',
            '--write-quarantine=no',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($command->called);
    }

    public function testCommandRoutesResidentScopeToValidator(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            public bool $residentCalled = false;
            protected function makeResidentValidator(): LegacyScopeValidatorInterface
            {
                return new class($this) implements LegacyScopeValidatorInterface {
                    public function __construct(private readonly object $parent) {}
                    public function validate(DryRunReport $report, array $options): DryRunReport
                    {
                        $this->parent->residentCalled = true;
                        $report->setSourceCount('uye_tanimlari', 1);
                        return $report;
                    }
                };
            }
        };

        $exitCode = $command->run([
            '--run-id=resident-route-run',
            '--company-id=12',
            '--legacy-connection=legacy',
            '--scope=resident',
            '--limit=10',
            '--write-quarantine=no',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($command->residentCalled);
    }

    public function testCommandRoutesDueScopeToValidator(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            public bool $dueCalled = false;
            protected function makeDueValidator(): LegacyScopeValidatorInterface
            {
                return new class($this) implements LegacyScopeValidatorInterface {
                    public function __construct(private readonly object $parent) {}
                    public function validate(DryRunReport $report, array $options): DryRunReport
                    {
                        $this->parent->dueCalled = true;
                        $report->setSourceCount('aidat_listesi', 1);
                        return $report;
                    }
                };
            }
        };

        $exitCode = $command->run([
            '--run-id=due-route-run',
            '--company-id=12',
            '--legacy-connection=legacy',
            '--scope=due',
            '--limit=10',
            '--write-quarantine=no',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($command->dueCalled);
    }

    public function testCommandRoutesPaymentScopeToValidator(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            public bool $paymentCalled = false;
            protected function makePaymentValidator(): LegacyScopeValidatorInterface
            {
                return new class($this) implements LegacyScopeValidatorInterface {
                    public function __construct(private readonly object $parent) {}
                    public function validate(DryRunReport $report, array $options): DryRunReport
                    {
                        $this->parent->paymentCalled = true;
                        $report->setSourceCount('tahsilat_listesi', 1);
                        return $report;
                    }
                };
            }
        };

        $exitCode = $command->run([
            '--run-id=payment-route-run',
            '--company-id=12',
            '--legacy-connection=legacy',
            '--scope=payment',
            '--limit=10',
            '--write-quarantine=no',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($command->paymentCalled);
    }

    public function testCommandRoutesDepositScopeToValidator(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            public bool $depositCalled = false;
            protected function makeDepositValidator(): LegacyScopeValidatorInterface
            {
                return new class($this) implements LegacyScopeValidatorInterface {
                    public function __construct(private readonly object $parent) {}
                    public function validate(DryRunReport $report, array $options): DryRunReport
                    {
                        $this->parent->depositCalled = true;
                        $report->setSourceCount('depozito_listesi', 1);
                        return $report;
                    }
                };
            }
        };

        $exitCode = $command->run([
            '--run-id=deposit-route-run',
            '--company-id=12',
            '--legacy-connection=legacy',
            '--scope=deposit',
            '--limit=10',
            '--write-quarantine=no',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($command->depositCalled);
    }

    public function testScopeAllRoutesImplementedValidatorsInOrder(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            /** @var list<string> */
            public array $calls = [];
            protected function makeSiteValidator(): LegacyScopeValidatorInterface { return $this->makeRecordingValidator('site'); }
            protected function makeResidentValidator(): LegacyScopeValidatorInterface { return $this->makeRecordingValidator('resident'); }
            protected function makeDueValidator(): LegacyScopeValidatorInterface { return $this->makeRecordingValidator('due'); }
            protected function makePaymentValidator(): LegacyScopeValidatorInterface { return $this->makeRecordingValidator('payment'); }
            protected function makeDepositValidator(): LegacyScopeValidatorInterface { return $this->makeRecordingValidator('deposit'); }
            private function makeRecordingValidator(string $scope): LegacyScopeValidatorInterface
            {
                return new class($this, $scope) implements LegacyScopeValidatorInterface {
                    public function __construct(private readonly object $parent, private readonly string $scope) {}
                    public function validate(DryRunReport $report, array $options): DryRunReport
                    {
                        $this->parent->calls[] = $this->scope;
                        $report->setSourceCount($this->scope . '_table', 1);
                        $report->setTargetCandidateCount($this->scope . '_target', 1);
                        return $report;
                    }
                };
            }
        };
        $report = $command->applyScopeValidation(
            $command->buildDesignOnlyReport([
                'run-id' => 'all-order',
                'company-id' => '12',
                'legacy-connection' => 'legacy',
                'scope' => 'all',
                'limit' => '100',
                'write-quarantine' => 'no',
            ]),
            ['scope' => 'all']
        );
        $data = $report->toArray();
        $this->assertSame(['site', 'resident', 'due', 'payment', 'deposit'], $command->calls);
        $this->assertSame(1, (int) ($data['source_counts']['site.site_table'] ?? 0));
        $this->assertSame(1, (int) ($data['target_candidate_counts']['deposit.deposit_target'] ?? 0));
    }

    public function testScopeAllBlockerMakesFinalStatusNoGo(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            public function buildDesignOnlyReport(array $options): DryRunReport
            {
                return new DryRunReport((string) $options['run-id'], (int) $options['company-id'], (string) $options['scope']);
            }
            protected function makeSiteValidator(): LegacyScopeValidatorInterface
            {
                return new class implements LegacyScopeValidatorInterface {
                    public function validate(DryRunReport $report, array $options): DryRunReport
                    {
                        $report->addBlocker('X', 'blocker');
                        return $report;
                    }
                };
            }
        };
        $report = $command->applyScopeValidation(
            $command->buildDesignOnlyReport(['run-id' => 'r', 'company-id' => '1', 'scope' => 'all']),
            ['scope' => 'all']
        );
        $this->assertSame('NO_GO', (string) $report->toArray()['go_no_go_status']);
    }

    public function testScopeAllWarningWithoutBlockerMakesReview(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            public function buildDesignOnlyReport(array $options): DryRunReport
            {
                return new DryRunReport((string) $options['run-id'], (int) $options['company-id'], (string) $options['scope']);
            }
            protected function makeSiteValidator(): LegacyScopeValidatorInterface
            {
                return new class implements LegacyScopeValidatorInterface {
                    public function validate(DryRunReport $report, array $options): DryRunReport
                    {
                        $report->addWarning('X', 'warn');
                        return $report;
                    }
                };
            }
        };
        $report = $command->applyScopeValidation(
            $command->buildDesignOnlyReport(['run-id' => 'r', 'company-id' => '1', 'scope' => 'all']),
            ['scope' => 'all']
        );
        $this->assertSame('REVIEW', (string) $report->toArray()['go_no_go_status']);
    }

    public function testScopeAllCleanCanBeGoWithTestDouble(): void
    {
        $command = new class (service('logger'), service('commands')) extends LegacyDryRunAnalyzeCommand {
            public function buildDesignOnlyReport(array $options): DryRunReport
            {
                return new DryRunReport((string) $options['run-id'], (int) $options['company-id'], (string) $options['scope']);
            }
            protected function makeSiteValidator(): LegacyScopeValidatorInterface { return new class implements LegacyScopeValidatorInterface { public function validate(DryRunReport $report, array $options): DryRunReport { return $report; } }; }
            protected function makeResidentValidator(): LegacyScopeValidatorInterface { return new class implements LegacyScopeValidatorInterface { public function validate(DryRunReport $report, array $options): DryRunReport { return $report; } }; }
            protected function makeDueValidator(): LegacyScopeValidatorInterface { return new class implements LegacyScopeValidatorInterface { public function validate(DryRunReport $report, array $options): DryRunReport { return $report; } }; }
            protected function makePaymentValidator(): LegacyScopeValidatorInterface { return new class implements LegacyScopeValidatorInterface { public function validate(DryRunReport $report, array $options): DryRunReport { return $report; } }; }
            protected function makeDepositValidator(): LegacyScopeValidatorInterface { return new class implements LegacyScopeValidatorInterface { public function validate(DryRunReport $report, array $options): DryRunReport { return $report; } }; }
        };
        $report = $command->applyScopeValidation(
            $command->buildDesignOnlyReport(['run-id' => 'r', 'company-id' => '1', 'scope' => 'all']),
            ['scope' => 'all']
        );
        $this->assertSame('GO', (string) $report->toArray()['go_no_go_status']);
    }
}

