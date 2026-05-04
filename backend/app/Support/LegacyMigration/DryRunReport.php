<?php

namespace App\Support\LegacyMigration;

final class DryRunReport
{
    /** @var array<string,int> */
    private array $sourceCounts = [];
    /** @var array<string,int> */
    private array $targetCandidateCounts = [];
    /** @var list<array{code:string,message:string,context:array<string,mixed>}> */
    private array $warnings = [];
    /** @var list<array{code:string,message:string,context:array<string,mixed>}> */
    private array $blockers = [];
    /** @var list<array{entity_type:string,legacy_table:string,legacy_id:int|string|null,error_message:string,payload:array<string,mixed>}> */
    private array $quarantineCandidates = [];

    public function __construct(
        private readonly string $runId,
        private readonly int $companyId,
        private readonly string $scope
    ) {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function addWarning(string $code, string $message, array $context = []): void
    {
        $this->warnings[] = ['code' => $code, 'message' => $message, 'context' => $context];
    }

    /**
     * @param array<string,mixed> $context
     */
    public function addBlocker(string $code, string $message, array $context = []): void
    {
        $this->blockers[] = ['code' => $code, 'message' => $message, 'context' => $context];
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function addQuarantineCandidate(string $entityType, string $legacyTable, int|string|null $legacyId, string $errorMessage, array $payload = []): void
    {
        $this->quarantineCandidates[] = [
            'entity_type' => $entityType,
            'legacy_table' => $legacyTable,
            'legacy_id' => $legacyId,
            'error_message' => $errorMessage,
            'payload' => $payload,
        ];
    }

    public function setSourceCount(string $entity, int $count): void
    {
        $this->sourceCounts[$entity] = $count;
    }

    public function setTargetCandidateCount(string $entity, int $count): void
    {
        $this->targetCandidateCounts[$entity] = $count;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'run_id' => $this->runId,
            'company_id' => $this->companyId,
            'scope' => $this->scope,
            'source_counts' => $this->sourceCounts,
            'target_candidate_counts' => $this->targetCandidateCounts,
            'warnings' => $this->warnings,
            'blockers' => $this->blockers,
            'quarantine_candidates' => $this->quarantineCandidates,
            'go_no_go_status' => $this->resolveGoNoGoStatus(),
        ];
    }

    public function toJson(): string
    {
        return (string) json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private function resolveGoNoGoStatus(): string
    {
        if (count($this->blockers) > 0) {
            return 'NO_GO';
        }
        if (count($this->quarantineCandidates) > 0 || count($this->warnings) > 0) {
            return 'REVIEW';
        }
        return 'GO';
    }
}

