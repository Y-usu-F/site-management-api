# Dry-Run Acceptance Criteria

Bu dokuman dry-run sonrasinda migration'in production'a gecis uygunlugunu belirler.

## GO Criteria

- Unresolved **P0 quarantine** count = 0
- `retry_count >= 3` blocker count = 0
- Duplicate `legacy_id` count = 0
- Cross-tenant issue count = 0
- Monetary mismatch sayisi/tutari onayli tolerans icinde
- `mapping_decisions_frozen.md` kararlarina aykiri satir kalmamis
- Rollback proseduru dry-run ortaminda dogrulanmis

## NO-GO Criteria

- Herhangi bir cross-tenant issue
- Unresolved orphan P0 satirlar
- Unresolved critical enum/status mapping satirlari
- Unresolved monetary mismatch satirlari
- Rollback test edilmemis veya basarisiz
- `retry_count >= 3` unresolved blocker satirlar

## P0 Siniflandirma Notu

P0 kapsaminda minimum:

- Tenant izolasyonu ihlali
- Finansal tutarsizlik (due/payment/deposit/legal debt)
- Parent-child referans zorunlulugu (kritik orphan)
- Critical status/type map edilemeyen satirlar

## Tolerance Governance

- Monetary tolerance degeri teknik ekip + urun + finans tarafindan ortak onaylanir.
- Tolerance disi satirlar otomatik NO-GO sayilir.

