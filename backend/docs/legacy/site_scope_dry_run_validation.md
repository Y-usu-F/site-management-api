# Site Scope Dry-Run Validation

Bu dokuman `scope=site` icin read-only dry-run dogrulama tasarimini tanimlar.

## Validation Matrix

| Validation | Legacy table | Target entity | Severity | Suggested error code | Acceptance criteria |
|---|---|---|---|---|---|
| Duplicate block name/code per site | `blok_tanimlari` | `blocks` | blocker | `SITE_DUPLICATE_BLOCK` | Ayni site icinde blok kodu/adi tekil olmalı |
| Duplicate unit number per block/floor | `daire_tanimlari` | `units` | blocker | `SITE_DUPLICATE_UNIT_NO` | Ayni block+floor icinde `unit_no` duplicate olmamali |
| Missing unit number | `daire_tanimlari` | `units` | quarantine | `SITE_MISSING_UNIT_NO` | `unit_no` bos/null ise import adayi quarantine |
| Missing block reference | `daire_tanimlari` | `units` | blocker | `SITE_MISSING_BLOCK_REF` | Unit satiri gecerli block eslesmesi olmadan ilerlememeli |
| Invalid floor value | `daire_tanimlari` | `floors/units` | warning | `SITE_INVALID_FLOOR_VALUE` | Kat degeri parse edilemezse warning + normalize fallback/queue |
| Unit area numeric validation | `daire_tanimlari` | `units` | warning | `SITE_INVALID_UNIT_AREA` | Alan metin veya negatif ise warning/quarantine karari dictionary'e gore |
| Orphan block source rows | `blok_tanimlari` | `blocks` | quarantine | `SITE_ORPHAN_BLOCK` | Site referansi olmayan block satiri quarantine |
| Orphan unit source rows | `daire_tanimlari` | `units` | quarantine | `SITE_ORPHAN_UNIT` | Block/site referansi cozulmeyen unit satiri quarantine |
| legacy_id uniqueness expectation (blocks) | `blok_tanimlari` | `blocks.legacy_id` | blocker | `SITE_DUPLICATE_LEGACY_ID_BLOCK` | Hedefte `blocks.legacy_id` duplicate olmamali |
| legacy_id uniqueness expectation (units) | `daire_tanimlari` | `units.legacy_id` | blocker | `SITE_DUPLICATE_LEGACY_ID_UNIT` | Hedefte `units.legacy_id` duplicate olmamali |

## Uygulama Notlari (Design)

- Bu scope yalnizca okuma yapar; target yazimi yoktur.
- `severity=quarantine` olan satirlar, `--write-quarantine=yes` modunda sadece `migration_quarantine_logs` adayina donusur.
- `severity=blocker` tespit edilirse scope sonucu `NO_GO` olur.
- `severity=warning` tek basina `REVIEW` statusu uretir.

