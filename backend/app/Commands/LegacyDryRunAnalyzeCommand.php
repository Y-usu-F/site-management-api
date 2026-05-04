<?php

namespace App\Commands;

use App\Support\LegacyMigration\DryRunReport;
use App\Support\LegacyMigration\Contracts\LegacyScopeValidatorInterface;
use App\Support\LegacyMigration\Validators\DepositScopeValidator;
use App\Support\LegacyMigration\Validators\DueScopeValidator;
use App\Support\LegacyMigration\Validators\PaymentScopeValidator;
use App\Support\LegacyMigration\Validators\ResidentScopeValidator;
use App\Support\LegacyMigration\Validators\SiteScopeValidator;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LegacyDryRunAnalyzeCommand extends BaseCommand
{
    protected $group = 'Legacy';
    protected $name = 'legacy:dry-run-analyze';
    protected $description = 'Read-only legacy dry-run analyzer skeleton';
    protected $usage = 'legacy:dry-run-analyze --run-id=... --company-id=... --legacy-connection=... --scope=... --limit=... --write-quarantine=yes|no --format=json|text';

    /**
     * @param list<string> $params
     */
    public function run(array $params): int
    {
        $options = $this->parseOptions($params);
        $errors = $this->validateRequiredOptions($options);
        if ($errors !== []) {
            foreach ($errors as $error) {
                CLI::error($error);
            }
            return 1;
        }

        $report = $this->applyScopeValidation($this->buildDesignOnlyReport($options), $options);
        CLI::write($this->formatReportOutput($report, $options['format'] ?? 'text'));
        CLI::write($this->designOnlyMessage());

        return 0;
    }

    public function designOnlyMessage(): string
    {
        return 'NOT IMPLEMENTED: analyzer validation design only';
    }

    /**
     * @param array<string,string> $options
     */
    public function buildDesignOnlyReport(array $options): DryRunReport
    {
        $report = new DryRunReport(
            (string) $options['run-id'],
            (int) $options['company-id'],
            (string) $options['scope']
        );
        $report->addWarning('DESIGN_ONLY', 'Validation logic is not implemented yet', [
            'legacy_connection' => (string) ($options['legacy-connection'] ?? ''),
            'limit' => (string) ($options['limit'] ?? ''),
            'write_quarantine' => (string) ($options['write-quarantine'] ?? ''),
        ]);
        return $report;
    }

    protected function makeSiteValidator(): LegacyScopeValidatorInterface
    {
        return new SiteScopeValidator();
    }

    protected function makeResidentValidator(): LegacyScopeValidatorInterface
    {
        return new ResidentScopeValidator();
    }

    protected function makeDueValidator(): LegacyScopeValidatorInterface
    {
        return new DueScopeValidator();
    }

    protected function makePaymentValidator(): LegacyScopeValidatorInterface
    {
        return new PaymentScopeValidator();
    }

    protected function makeDepositValidator(): LegacyScopeValidatorInterface
    {
        return new DepositScopeValidator();
    }

    /**
     * @param array<string,string> $options
     */
    public function applyScopeValidation(DryRunReport $report, array $options): DryRunReport
    {
        $scope = (string) ($options['scope'] ?? '');
        if ($scope === 'all') {
            return $this->runAllScopes($report, $options);
        }
        $validator = $this->resolveValidatorByScope($scope);
        if ($validator === null) {
            $report->addWarning('SCOPE_NOT_IMPLEMENTED', 'scope validator not implemented yet', [
                'scope' => $scope,
            ]);
            return $report;
        }
        return $validator->validate($report, $options);
    }

    /**
     * @param array<string,string> $options
     */
    protected function runAllScopes(DryRunReport $report, array $options): DryRunReport
    {
        $ordered = [
            'site' => $this->makeSiteValidator(),
            'resident' => $this->makeResidentValidator(),
            'due' => $this->makeDueValidator(),
            'payment' => $this->makePaymentValidator(),
            'deposit' => $this->makeDepositValidator(),
        ];

        foreach ($ordered as $scope => $validator) {
            $before = $report->toArray();
            $report = $validator->validate($report, $options);
            $this->duplicateScopedCounts($report, $scope, (array) ($before['source_counts'] ?? []), (array) ($before['target_candidate_counts'] ?? []));
        }

        return $report;
    }

    protected function resolveValidatorByScope(string $scope): ?LegacyScopeValidatorInterface
    {
        return match ($scope) {
            'site' => $this->makeSiteValidator(),
            'resident' => $this->makeResidentValidator(),
            'due' => $this->makeDueValidator(),
            'payment' => $this->makePaymentValidator(),
            'deposit' => $this->makeDepositValidator(),
            default => null,
        };
    }

    /**
     * @param array<string,int> $previousSourceCounts
     * @param array<string,int> $previousTargetCounts
     */
    protected function duplicateScopedCounts(DryRunReport $report, string $scope, array $previousSourceCounts, array $previousTargetCounts): void
    {
        $current = $report->toArray();
        $source = (array) ($current['source_counts'] ?? []);
        foreach ($source as $entity => $count) {
            if (str_contains((string) $entity, '.')) {
                continue;
            }
            if (! array_key_exists((string) $entity, $previousSourceCounts) || $previousSourceCounts[(string) $entity] !== (int) $count) {
                $report->setSourceCount($scope . '.' . (string) $entity, (int) $count);
            }
        }

        $target = (array) ($current['target_candidate_counts'] ?? []);
        foreach ($target as $entity => $count) {
            if (str_contains((string) $entity, '.')) {
                continue;
            }
            if (! array_key_exists((string) $entity, $previousTargetCounts) || $previousTargetCounts[(string) $entity] !== (int) $count) {
                $report->setTargetCandidateCount($scope . '.' . (string) $entity, (int) $count);
            }
        }
    }

    public function formatReportOutput(DryRunReport $report, string $format): string
    {
        if ($format === 'json') {
            return $report->toJson();
        }

        $data = $report->toArray();
        $lines = [
            'Legacy Dry-Run Analyze (Design-Only)',
            'run_id=' . (string) $data['run_id'],
            'company_id=' . (string) $data['company_id'],
            'scope=' . (string) $data['scope'],
            'go_no_go_status=' . (string) $data['go_no_go_status'],
            'warnings=' . (string) count((array) $data['warnings']),
            'blockers=' . (string) count((array) $data['blockers']),
            'quarantine_candidates=' . (string) count((array) $data['quarantine_candidates']),
        ];
        return implode(PHP_EOL, $lines);
    }

    /**
     * @param list<string> $params
     * @return array<string,string>
     */
    public function parseOptions(array $params): array
    {
        $options = $this->parseOptionTokens($params);
        $allowedKeys = ['run-id', 'company-id', 'legacy-connection', 'scope', 'limit', 'write-quarantine', 'format'];

        // CI4 may normalize options before run(array $params); prefer CLI option API first.
        foreach ($allowedKeys as $key) {
            if (isset($options[$key]) && $options[$key] !== '') {
                continue;
            }
            $cliValue = CLI::getOption($key);
            if ($cliValue !== null && $cliValue !== '') {
                $options[$key] = trim((string) $cliValue);
            }
        }

        // Fallback for runtime contexts where options are only visible in argv.
        if (count(array_intersect(array_keys($options), $allowedKeys)) === 0) {
            $argvTokens = $this->readArgvOptionTokens();
            $options = array_merge($options, $this->parseOptionTokens($argvTokens));
        }

        return $options;
    }

    /**
     * @param list<string> $tokens
     * @return array<string,string>
     */
    protected function parseOptionTokens(array $tokens): array
    {
        $options = [];
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = trim((string) ($tokens[$i] ?? ''));
            if (! str_starts_with($token, '--')) {
                continue;
            }

            $payload = substr($token, 2);
            $key = '';
            $value = '';
            if (str_contains($payload, '=')) {
                $parts = explode('=', $payload, 2);
                $key = trim((string) ($parts[0] ?? ''));
                $value = trim((string) ($parts[1] ?? ''));
            } else {
                $key = trim($payload);
                $next = trim((string) ($tokens[$i + 1] ?? ''));
                if ($next !== '' && ! str_starts_with($next, '--')) {
                    $value = $next;
                    $i++;
                }
            }

            if ($key !== '') {
                $options[$key] = $value;
            }
        }
        return $options;
    }

    /**
     * @return list<string>
     */
    protected function readArgvOptionTokens(): array
    {
        $argv = $_SERVER['argv'] ?? [];
        if (! is_array($argv)) {
            return [];
        }
        return array_values(array_map(static fn ($v): string => (string) $v, $argv));
    }

    /**
     * @param array<string,string> $options
     * @return list<string>
     */
    public function validateRequiredOptions(array $options): array
    {
        $required = ['run-id', 'company-id', 'legacy-connection', 'scope', 'limit', 'write-quarantine'];
        $errors = [];
        foreach ($required as $key) {
            if (! isset($options[$key]) || $options[$key] === '') {
                $errors[] = 'Missing required option: --' . $key;
            }
        }

        if (isset($options['write-quarantine']) && ! in_array($options['write-quarantine'], ['yes', 'no'], true)) {
            $errors[] = 'Invalid --write-quarantine value. Allowed: yes|no';
        }
        if (isset($options['format']) && ! in_array($options['format'], ['json', 'text'], true)) {
            $errors[] = 'Invalid --format value. Allowed: json|text';
        }

        return $errors;
    }
}

