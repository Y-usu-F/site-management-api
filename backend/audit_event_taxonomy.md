# Audit Event Taxonomy (Starter Kit)

Event naming standard:

- Format: `domain.resource.action(.outcome)`
- Allowed chars: lowercase letters, digits, underscore, dot
- Example: `auth.login.success`, `rbac.role.assigned`, `security.access.forbidden`

Critical events:

- Auth lifecycle:
  - `auth.login.success`
  - `auth.login.failed`
  - `auth.refresh.success`
  - `auth.refresh.failed`
  - `auth.logout.success`
- RBAC:
  - `rbac.role.assigned`
  - `rbac.role.revoked`
- Security:
  - `security.access.forbidden`
  - `security.tenant.violation`

Standard audit payload fields:

- `company_id`
- `actor_user_id`
- `action`
- `entity_type`
- `entity_id`
- `old_values`
- `new_values`
- `ip_address`
- `user_agent`
- `request_id`
- `occurred_at`
