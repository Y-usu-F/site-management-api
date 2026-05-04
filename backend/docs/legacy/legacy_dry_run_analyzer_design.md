# Legacy Dry-Run Analyzer Design

Bu dokuman yalnizca tasarimdir. Import yapmaz, hedef is verisi yazmaz.

## Command

- Komut adi: `php spark legacy:dry-run-analyze`

## Required Options

- `--run-id`
- `--company-id`
- `--legacy-connection`
- `--scope`
- `--limit`
- `--write-quarantine=yes/no`

## Optional Options

- `--format=json/text` (default: `text`)

## Supported Scopes

- `site`
- `resident`
- `due`
- `payment`
- `deposit`
- `request`
- `meter`
- `asset`
- `document`
- `legal`
- `notification`
- `all`

## Scope Bazli Validation Kapsami

### site
- duplicate block/unit
- missing unit number
- orphan block

### resident
- missing name/contact
- duplicate resident identity
- invalid occupancy date
- orphan unit
 - durum: `implemented` (read-only validator mevcut)

### due
- invalid due date
- unknown due status/type
- negative amount
- orphan unit/resident
 - durum: `implemented` (read-only validator mevcut)

### payment
- unknown method/status
- negative amount
- allocation mismatch
- orphan due/payment reference
- online provider event mapping safety
- bank reconciliation candidate warnings
- durum: `implemented` (read-only validator mevcut)

### deposit
- unknown transaction type/status
- negative balance
- orphan unit/resident
- refund/deduction icin missing reason code
- invalid amount/balance/date checks
- duplicate deposit natural key safety
- durum: `implemented` (read-only validator mevcut)

### request
- unknown request status
- unknown category
- orphan resident/unit reference

### meter
- unknown meter type
- duplicate serial
- invalid reading period

### asset
- duplicate asset_no/serial
- orphan location reference
- invalid lifecycle date sequence

### document
- missing file metadata
- duplicate checksum/version collision
- orphan owner reference

### legal
- unknown case status
- unknown event type
- orphan resident/unit
- invalid hearing date

### notification
- unknown channel/status
- missing recipient
- provider raw status handling

### all
- Yukaridaki tum scope kontrollerini sirayla calistirir.
- Calisma sirasi: `site -> resident -> due -> payment -> deposit`
- Tum implemented scope warning/blocker/quarantine sonuclari tek raporda birlesir.
- `source_counts` ve `target_candidate_counts` alanlarinda `scope.entity` formatinda aggregate kirilim uretilir.

## Analyzer Output Contract

Hem JSON hem text ozet ayni semantiigi tasir:

- `run_id`
- `company_id`
- `scope`
- `source_counts`
- `target_candidate_counts`
- `warnings`
- `blockers`
- `quarantine_candidates`
- `go_no_go_status`

Bu contract, `DryRunReport` yardimci sinifinda standardize edilir:

- `App\Support\LegacyMigration\DryRunReport`
- Metodlar:
  - `addWarning(code, message, context = [])`
  - `addBlocker(code, message, context = [])`
  - `addQuarantineCandidate(entityType, legacyTable, legacyId, errorMessage, payload = [])`
  - `setSourceCount(entity, count)`
  - `setTargetCandidateCount(entity, count)`
  - `toArray()`
  - `toJson()`

### JSON Ornek

```json
{
  "run_id": "dryrun-2026-05-01-01",
  "company_id": 42,
  "scope": "due",
  "source_counts": {
    "aidat_listesi": 1240
  },
  "target_candidate_counts": {
    "due_items": 1202
  },
  "warnings": [
    "24 rows have unknown due status"
  ],
  "blockers": [
    "3 rows have negative amount"
  ],
  "quarantine_candidates": 41,
  "go_no_go_status": "NO_GO"
}
```

## Quarantine Davranisi (Design)

- `--write-quarantine=no`: sadece raporlar, DB write yok.
- `--write-quarantine=yes`: sadece `migration_quarantine_logs` yazimi (opsiyonel mode).
- Bu patchte implementasyon **yok**, sadece contract tanimi var.

## SITE Scope Referansi

- `scope=site` icin detayli validation tasarimi:
  - `site_scope_dry_run_validation.md`

## Implemented Scope Durumu

- `site`: implemented (read-only)
- `resident`: implemented (read-only)
- `due`: implemented (read-only)
- `payment`: implemented (read-only)
- `deposit`: implemented (read-only)
- digerleri: design-only, `scope validator not implemented yet`

## Exit Code Tasarimi

- `0`: analiz tamamlandi, blocker yok (GO)
- `1`: opsiyon/validation hatasi
- `2`: analiz tamamlandi ama blocker var (NO_GO)

## Go/No-Go Status Rules

- `NO_GO`: blocker sayisi > 0
- `REVIEW`: blocker yok, fakat warning veya quarantine candidate var
- `GO`: blocker/warning/quarantine candidate yok

## Final Dry-Run Gate

- Production import oncesi minimum zorunlu scope'lar: `site`, `resident`, `due`, `payment`, `deposit`.
- Gercek import tasarimina gecmeden once `--scope=all` calistirilmasi zorunludur.
- JSON dry-run raporu `run_id` ile birlikte arşivlenmelidir.
- `NO_GO` sonucu importu bloke eder.
- `REVIEW` sonucu manuel onay gerektirir.
- `GO` sonucu kontrollu import tasarimina gecise izin verir.

