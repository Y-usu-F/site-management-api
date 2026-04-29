<?php

namespace App\Services\Common;

final class AuditEventTaxonomy
{
    /**
     * Event naming standard:
     * domain.resource.action(.outcome)
     *
     * Examples:
     * - auth.login.success
     * - rbac.role.assigned
     * - profile.update.success
     * - security.access.forbidden
     *
     * @var list<string>
     */
    public const EVENTS = [
        'auth.login.success',
        'auth.login.failed',
        'auth.login.blocked_inactive_user',
        'auth.login.blocked_rate_limit',
        'auth.refresh.success',
        'auth.refresh.failed',
        'auth.refresh.reuse_detected',
        'auth.logout.success',
        'profile.update.success',
        'profile.password_change.success',
        'profile.password_change.failed',
        'auth.forgot_password.requested',
        'auth.reset_password.success',
        'auth.reset_password.failed',
        'rbac.role.assigned',
        'rbac.role.revoked',
        'security.access.forbidden',
        'security.tenant.violation',
    ];

    /**
     * @var list<string>
     */
    public const CRITICAL_EVENTS = [
        'auth.login.success',
        'auth.login.failed',
        'auth.refresh.success',
        'auth.refresh.failed',
        'auth.logout.success',
        'rbac.role.assigned',
        'rbac.role.revoked',
        'security.access.forbidden',
        'security.tenant.violation',
    ];

    public static function isValidEventName(string $event): bool
    {
        return preg_match('/^[a-z]+(\.[a-z0-9_]+){2,}$/', $event) === 1;
    }

    public static function isKnownEvent(string $event): bool
    {
        return in_array($event, self::EVENTS, true);
    }
}
