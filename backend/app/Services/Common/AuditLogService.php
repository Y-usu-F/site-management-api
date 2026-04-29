<?php

namespace App\Services\Common;

use App\Models\AuditLogModel;
use InvalidArgumentException;
use Throwable;

class AuditLogService
{
    /**
     * @deprecated Use AuditEventTaxonomy::EVENTS
     * @var list<string>
     */
    public const EVENTS = AuditEventTaxonomy::EVENTS;

    /**
     * @var list<string>
     */
    private array $sensitiveKeys = [
        'password',
        'password_hash',
        'token',
        'secret',
        'refresh_token',
        'access_token',
        'reset_token',
        'authorization',
        'cookie',
    ];

    private AuditLogModel $auditLogModel;

    public function __construct(?AuditLogModel $auditLogModel = null)
    {
        $this->auditLogModel = $auditLogModel ?? new AuditLogModel();
    }

    /**
     * @param array<string, mixed> $context
     */
    public function recordEvent(string $event, array $context = []): bool
    {
        try {
            if (! AuditEventTaxonomy::isValidEventName($event)) {
                throw new InvalidArgumentException('Audit event naming standardina uymuyor');
            }

            $request = service('request');
            $normalized = $this->normalizeContext($event, $context, $request);
            $meta = [
                'known_event' => AuditEventTaxonomy::isKnownEvent($event),
                'critical_event' => in_array($event, AuditEventTaxonomy::CRITICAL_EVENTS, true),
                'taxonomy_version' => 1,
                'meta' => $normalized['meta'],
            ];

            $this->auditLogModel->insert([
                'company_id' => $normalized['company_id'],
                'user_id' => $normalized['actor_user_id'],
                'action' => $normalized['action'],
                'event' => $event,
                'actor_user_id' => $normalized['actor_user_id'],
                'target_user_id' => $normalized['target_user_id'],
                'status' => $normalized['status'],
                'ip' => $normalized['ip_address'],
                'ip_address' => $normalized['ip_address'],
                'user_agent' => $normalized['user_agent'],
                'request_id' => $normalized['request_id'],
                'occurred_at' => $normalized['occurred_at'],
                'entity_type' => $normalized['entity_type'],
                'entity_id' => $normalized['entity_id'],
                'old_data' => json_encode($normalized['old_values']),
                'new_data' => json_encode($normalized['new_values']),
                'old_values' => json_encode($normalized['old_values']),
                'new_values' => json_encode($normalized['new_values']),
                'meta' => json_encode($meta),
            ]);

            return true;
        } catch (Throwable $e) {
            log_message('error', 'Audit write failed: {message}', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function record(string $action, array $payload = []): bool
    {
        return $this->recordEvent($action, $payload);
    }

    /**
     * @param array<string,mixed> $context
     * @return array{
     *   company_id:int|null,
     *   actor_user_id:int|null,
     *   action:string,
     *   entity_type:string,
     *   entity_id:string|null,
     *   old_values:array<string,mixed>,
     *   new_values:array<string,mixed>,
     *   ip_address:string|null,
     *   user_agent:string|null,
     *   request_id:string|null,
     *   occurred_at:string,
     *   target_user_id:int|null,
     *   status:string,
     *   meta:array<string,mixed>
     * }
     */
    private function normalizeContext(string $event, array $context, object $request): array
    {
        $oldValues = $this->sanitize((array) ($context['old_values'] ?? $context['old_data'] ?? []));
        $newValues = $this->sanitize((array) ($context['new_values'] ?? $context['new_data'] ?? []));
        $meta = $this->sanitize((array) ($context['meta'] ?? []));

        return [
            'company_id' => isset($context['company_id']) ? (int) $context['company_id'] : ($request->company_id ?? null),
            'actor_user_id' => isset($context['actor_user_id']) ? (int) $context['actor_user_id'] : ($request->user?->id ?? null),
            'action' => $context['action'] ?? $event,
            'entity_type' => (string) ($context['entity_type'] ?? 'system'),
            'entity_id' => isset($context['entity_id']) ? (string) $context['entity_id'] : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => isset($context['ip_address'])
                ? (string) $context['ip_address']
                : (isset($context['ip']) ? (string) $context['ip'] : (method_exists($request, 'getIPAddress') ? (string) $request->getIPAddress() : null)),
            'user_agent' => isset($context['user_agent'])
                ? (string) $context['user_agent']
                : (method_exists($request, 'getUserAgent') ? (string) $request->getUserAgent()->getAgentString() : null),
            'request_id' => isset($context['request_id'])
                ? (string) $context['request_id']
                : (string) ($request->request_id ?? (method_exists($request, 'getHeaderLine') ? $request->getHeaderLine('X-Request-Id') : '')),
            'occurred_at' => isset($context['occurred_at']) && trim((string) $context['occurred_at']) !== ''
                ? (string) $context['occurred_at']
                : date('Y-m-d H:i:s'),
            'target_user_id' => isset($context['target_user_id']) ? (int) $context['target_user_id'] : null,
            'status' => (string) ($context['status'] ?? 'success'),
            'meta' => $meta,
        ];
    }

    private function sanitize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $result = [];
        foreach ($value as $key => $item) {
            $normalized = is_string($key) ? strtolower($key) : '';
            if ($normalized !== '' && in_array($normalized, $this->sensitiveKeys, true)) {
                $result[$key] = '***';
                continue;
            }

            $result[$key] = is_array($item) ? $this->sanitize($item) : $item;
        }

        return $result;
    }
}
