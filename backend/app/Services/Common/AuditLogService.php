<?php

namespace App\Services\Common;

use App\Models\AuditLogModel;
use Config\Database;
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
        if (! AuditEventTaxonomy::isValidEventName($event)) {
            return false;
        }

        try {
            $request = service('request');
            $normalized = $this->normalizeContext($event, $context, $request);
            $meta = [
                'known_event' => AuditEventTaxonomy::isKnownEvent($event),
                'critical_event' => in_array($event, AuditEventTaxonomy::CRITICAL_EVENTS, true),
                'taxonomy_version' => 1,
                'meta' => $normalized['meta'],
            ];

            $row = [
                'company_id' => $normalized['company_id'],
                'user_id' => $normalized['actor_user_id'],
                'action' => $normalized['action'],
                'event' => $event,
                'actor_user_id' => $normalized['actor_user_id'],
                'target_user_id' => $normalized['target_user_id'],
                'status' => $normalized['status'],
                'ip' => $normalized['ip_address'],
                'user_agent' => $normalized['user_agent'],
                'entity_type' => $normalized['entity_type'],
                'entity_id' => $normalized['entity_id'],
                'old_data' => $this->safeJsonEncode($normalized['old_values']),
                'new_data' => $this->safeJsonEncode($normalized['new_values']),
                'meta' => $this->safeJsonEncode($meta),
            ];

            $db = Database::connect();
            if ($db->fieldExists('ip_address', 'audit_logs')) {
                $row['ip_address'] = $normalized['ip_address'];
            }
            if ($db->fieldExists('request_id', 'audit_logs')) {
                $row['request_id'] = $normalized['request_id'];
            }
            if ($db->fieldExists('occurred_at', 'audit_logs')) {
                $row['occurred_at'] = $normalized['occurred_at'];
            }
            if ($db->fieldExists('old_values', 'audit_logs')) {
                $row['old_values'] = $this->safeJsonEncode($normalized['old_values']);
            }
            if ($db->fieldExists('new_values', 'audit_logs')) {
                $row['new_values'] = $this->safeJsonEncode($normalized['new_values']);
            }

            $this->auditLogModel->insert($row);

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

        $ipAddress = isset($context['ip_address'])
            ? (string) $context['ip_address']
            : (isset($context['ip']) ? (string) $context['ip'] : (method_exists($request, 'getIPAddress') ? (string) $request->getIPAddress() : null));
        $userAgent = isset($context['user_agent'])
            ? (string) $context['user_agent']
            : (method_exists($request, 'getUserAgent') ? (string) $request->getUserAgent()->getAgentString() : null);
        $requestId = isset($context['request_id'])
            ? (string) $context['request_id']
            : (string) ($request->request_id ?? (method_exists($request, 'getHeaderLine') ? $request->getHeaderLine('X-Request-Id') : ''));

        return [
            'company_id' => isset($context['company_id']) ? (int) $context['company_id'] : ($request->company_id ?? null),
            'actor_user_id' => isset($context['actor_user_id']) ? (int) $context['actor_user_id'] : ($request->user?->id ?? null),
            'action' => $this->limit((string) ($context['action'] ?? $event), 120),
            'entity_type' => $this->limit((string) ($context['entity_type'] ?? 'system'), 120),
            'entity_id' => isset($context['entity_id']) ? $this->limit((string) $context['entity_id'], 64) : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress !== null ? $this->limit($ipAddress, 45) : null,
            'user_agent' => $userAgent !== null ? $this->limit($userAgent, 255) : null,
            'request_id' => $requestId !== '' ? $this->limit($requestId, 64) : null,
            'occurred_at' => isset($context['occurred_at']) && trim((string) $context['occurred_at']) !== ''
                ? (string) $context['occurred_at']
                : date('Y-m-d H:i:s'),
            'target_user_id' => isset($context['target_user_id']) ? (int) $context['target_user_id'] : null,
            'status' => $this->limit((string) ($context['status'] ?? 'success'), 40),
            'meta' => $meta,
        ];
    }

    private function limit(string $value, int $maxLength): string
    {
        if ($maxLength <= 0 || $value === '') {
            return $value;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength);
        }

        return substr($value, 0, $maxLength);
    }

    private function safeJsonEncode(array $payload): string
    {
        $encoded = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($encoded === false) {
            return '{}';
        }

        return $encoded;
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
