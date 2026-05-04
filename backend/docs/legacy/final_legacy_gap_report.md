# Final Legacy Gap Report

## 1. Executive Summary

- Total legacy tables reviewed: **97** (from `sitebys.sql` static analysis).
- Clear mappings (FULL): **site/resident/due/payment/deposit core + announcement/request/meter/asset/document/legal base tables**.
- Partial mappings (PARTIAL): **finance ledger/cari, historical/status trail, provider/raw integration, assignment lifecycle, survey artifacts**.
- Missing modules (NONE): **full accounting ledger (cash/bank/AP-AR), procurement/vendor invoice flow, survey domain, e-commerce/cart domain**.
- Recommended schema additions:
  - P0: due interest model, import idempotency/lineage, reconciliation metadata, legal event dictionary hardening.
  - P1: household relation model, asset assignment lifecycle, status history tables.
  - P2: dynamic custom fields for `txtN` leftovers.

## 2. Table Mapping Matrix

| legacy_table | legacy_purpose | new_module | new_table | mapping_status | decision | reason |
|---|---|---|---|---|---|---|
| `blok_tanimlari` | block definitions | SITE-001 | `blocks` | FULL | migrate | core spatial model |
| `daire_tanimlari` | unit definitions | SITE-001 | `units` | FULL | migrate | core spatial model |
| `uye_tanimlari` | resident master | SITE-002 | `resident_profiles` | FULL | migrate | core resident model |
| `uye_malik_bilgileri` | owner occupancy | SITE-002 | `unit_occupancies` | PARTIAL | migrate with rules | reference/date cleanup needed |
| `onceki_kiracilar` | past tenancy | SITE-002 | `unit_occupancies` | PARTIAL | migrate with rules | historical state conversion needed |
| `uye_fert_tanimlari` | household members | SITE-002 | (missing relation table) | PARTIAL | defer + add schema | no direct normalized relation table |
| `uye_arac_bilgisi` | resident vehicles | SITE-002 | `resident_vehicles` | FULL | migrate | direct domain match |
| `aidat_grup_tanimlari` | due definition/group | SITE-003 | `due_definitions` | FULL | migrate | mapped with dictionary |
| `aidat_listesi` | recurring due items | SITE-003 | `due_items` | FULL | migrate | direct due item target |
| `borc_listesi` | debt items | SITE-003 | `due_items` | PARTIAL | migrate with rules | mixed legacy semantics (`txt*`) |
| `aidat_iade_listesi` | due refunds | SITE-003/004 | adjustment layer | PARTIAL | quarantine-first | explicit adjustment model limited |
| `borc_iade_listesi` | debt refunds | SITE-003/004 | adjustment layer | PARTIAL | quarantine-first | explicit adjustment model limited |
| `tahsilat_listesi` | collections/payments | SITE-004 | `payments` | FULL | migrate | method/status normalized |
| `odeme_listesi` | payment lines | SITE-004 | `payments` | PARTIAL | migrate with fallback | sparse/variant columns |
| `uye_online_odemeler` | online provider events | SITE-004 | `payment_events` | FULL | migrate | event model exists |
| `bankadan_gelen_veriler` | bank feed rows | SITE-004 | reconciliation/event | PARTIAL | defer partial | reconciliation engine still thin |
| `depozito_listesi` | deposit lifecycle | SITE-016 | `deposits`,`deposit_transactions` | FULL | migrate | direct module exists |
| `icra_takibi2` | legal case master | SITE-015 | `legal_cases` | FULL | migrate | direct legal case target |
| `icra_takibi_hareketi` | legal case events/debts | SITE-015 | `legal_case_events`,`legal_case_debts` | PARTIAL | migrate with split rules | event/debt split dictionary required |
| `istek_sikayetler` | request/complaint | SITE-005 | `service_requests` | FULL | migrate | direct module exists |
| `talepler` | requests | SITE-005 | `service_requests` | FULL | migrate | direct module exists |
| `duyurular` | announcements | SITE-007 | `announcements` | FULL | migrate | direct module exists |
| `duyuru_uyeler` | announcement targeting/read | SITE-007 | `announcement_targets`,`announcement_reads` | PARTIAL | migrate with rules | read/target split needed |
| `document_files` | file metadata | SITE-013 | `documents`,`document_versions` | FULL | migrate | direct module exists |
| `demirbaslar` | assets | SITE-010 | `assets` | FULL | migrate | direct module exists |
| `demirbas_zimmetler` | asset assignment | SITE-010 | assignment subdomain | PARTIAL | add schema then migrate | assignment lifecycle not explicit |
| `kasa_banka_tanimlari` | finance accounts | (missing) | (missing) | NONE | do not migrate now | accounting module out of current scope |
| `kasa_listesi` | cash ledger | (missing) | (missing) | NONE | do not migrate now | no double-entry ledger scope |
| `uye_cari_hareket` | resident balance movement evidence | Finance/Reconciliation | ledger-lite layer (`resident_balance_movements` or `finance_reconciliation_entries`) | PARTIAL | migrate as ledger-lite / reconciliation evidence | required for historical balance evidence and due/payment reconciliation; not a full accounting ledger |
| `tedarikci_tanimlari` | vendor master | (missing) | (missing) | NONE | backlog | procurement scope not present |
| `anket_*` | survey domain | (missing) | (missing) | NONE | ignore for now | not in product scope |
| `uye_alisveris_sepeti` | shopping/cart | (missing) | (missing) | IGNORE | ignore | obsolete/out-of-scope artifact |
| `site_aktarim_temp` | staging/temp | ETL | staging only | IGNORE | ignore | temporary migration helper |
| `excel_aktarim_listesi` | import helper | ETL | staging only | IGNORE | ignore | temporary migration helper |

## 3. Column Mapping Matrix

| legacy_table | legacy_column | meaning | target_table | target_column | mapping_status | decision |
|---|---|---|---|---|---|---|
| `blok_tanimlari` | `id` | legacy block id | `blocks` | `legacy_id` | FULL | migrate |
| `blok_tanimlari` | `txt1` | block name | `blocks` | `name` | FULL | migrate |
| `daire_tanimlari` | `id` | legacy unit id | `units` | `legacy_id` | FULL | migrate |
| `daire_tanimlari` | `txt1` | block name reference | `units` | `block_id` (resolved by block name/code) | PARTIAL | migrate with resolver |
| `daire_tanimlari` | `txt2` | unit number | `units` | `unit_no` | FULL | migrate |
| `daire_tanimlari` | `txt5` | gross area | `units` | `gross_area_m2` (or `meta_json`) | PARTIAL | migrate with transform |
| `daire_tanimlari` | `txt6` | net area | `units` | `net_area_m2` | FULL | migrate |
| `uye_tanimlari` | `txt5` | first name | `resident_profiles` | `first_name` | FULL | migrate |
| `uye_tanimlari` | `txt6` | last name | `resident_profiles` | `last_name` | FULL | migrate |
| `uye_tanimlari` | `txt11` | email | `resident_profiles` | `email` | FULL | migrate |
| `uye_tanimlari` | `txt9` | gsm phone | `resident_profiles` | `phone` | FULL | migrate |
| `uye_tanimlari` | `txt10` | alternate phone | `resident_profiles` | `phone_secondary`/`meta_json` | PARTIAL | migrate with extension |
| `aidat_listesi` | `tutar` | due amount | `due_items` | `amount` | FULL | migrate |
| `aidat_listesi` | `txt4` | process date | `due_items` | `issue_date`/`created_at` | PARTIAL | migrate with mapping rule |
| `aidat_listesi` | `txt5` | due date | `due_items` | `due_date` | FULL | migrate |
| `aidat_listesi` | `odendi`/`durumu` | payment status | `due_items` | `status` | FULL | migrate (dictionary normalized) |
| `aidat_listesi` | `txt2` | period label | `due_items` | `period_key` | PARTIAL | migrate with normalization |
| `aidat_listesi` | `txt3` | due group/type | `due_definitions`/`due_items` | `calculation_type`/`definition_id` | PARTIAL | migrate with resolver |
| `tahsilat_listesi` | `tutar` | payment amount | `payments` | `amount` | FULL | migrate |
| `tahsilat_listesi` | `txt1` | payment date | `payments` | `payment_date` | FULL | migrate |
| `tahsilat_listesi` | `txt7` | payment method text | `payments` | `method` | FULL | migrate (dictionary normalized) |
| `tahsilat_listesi` | `txt3` | payment status semantic | `payments` | `status` | FULL | migrate (`Aidat` -> `completed`) |
| `tahsilat_listesi` | `makbuz_no` | receipt number | `payments` | `reference_no`/`receipt_no` | PARTIAL | migrate with extension |
| `tahsilat_listesi` | `ref_no` | external/internal ref | `payments` | `reference_no` | PARTIAL | migrate with precedence rule |
| `uye_tanimlari` | `txt7` | national id | `resident_profiles` | (sensitive identity field) | PARTIAL | migrate masked/controlled |
| `aidat_listesi` | `txt6` | free description | `due_items` | `description`/`meta_json` | PARTIAL | migrate with truncation policy |
| `tahsilat_listesi` | `txt5` | account/cash text | `payments` | `account_ref`/`meta_json` | PARTIAL | migrate with finance backlog dependency |

## 4. Required New Schema Additions

### P0 (must-have before production compatibility)

| module | target_table | field/table to add | reason | migration risk | recommended action |
|---|---|---|---|---|---|
| Due/Finance | `due_items` + supporting table | interest/penalty rule model (`interest_rules` or equivalent) | legacy `gecikme_faiz_oranlari` parity | High | add dedicated rule table + deterministic calculator |
| Payment | `payments` | normalized `reference_no` strategy (receipt/external/internal) | `makbuz_no` + `ref_no` parity | Medium | add one canonical reference + source tag |
| Import/ETL | import lineage table or extended metadata | `import_run_id`, source hash, step status | controlled rollback/traceability | Medium | add minimal lineage metadata per imported entity |
| Legal | `legal_case_events` dictionary | strict event-type mapping table | split ambiguity in `icra_takibi_hareketi` | Medium | add controlled enum/dictionary table |
| Finance / Reconciliation | ledger-lite table | `resident_balance_movements` or `finance_reconciliation_entries` | `uye_cari_hareket` historical balance evidence and due/payment total reconciliation | High | add minimal ledger-lite/reconciliation table; avoid full accounting module scope |

### P1 (important but non-blocker)

| module | target_table | field/table to add | reason | migration risk | recommended action |
|---|---|---|---|---|---|
| Resident | relation table | household relation entity | `uye_fert_tanimlari` parity | Medium | add `resident_relations` model |
| Asset | assignment table | asset assignment lifecycle | `demirbas_zimmetler` parity | Medium | add `asset_assignments` with status timeline |
| Cross-module | status history tables | explicit state transition audit | legacy history tables parity | Low-Medium | add generic status-history pattern |
| Payment reconciliation | reconciliation table | bank row matching evidence | `bankadan_gelen_veriler` parity | Medium | add reconciliation candidates/results table |

### P2 (optional/future)

| module | target_table | field/table to add | reason | migration risk | recommended action |
|---|---|---|---|---|---|
| Dynamic fields | generic custom field tables | capture unresolved `txtN` tails | flexibility for edge legacy fields | Medium | implement only after product governance |
| Analytics | reporting mart tables | derived legacy report parity | not operationally required | Low | keep in BI backlog |

## 5. Do Not Add / Ignore List

Ignore from target schema (no migration into operational API tables):

- Temp/staging artifacts:
  - `site_aktarim_temp`
  - `excel_aktarim_listesi`
  - `toplu_secimler`
- Obsolete/out-of-scope commerce:
  - `uye_alisveris_sepeti`
- Legacy report/cache style artifacts:
  - `rapor_gelir` (use derived reporting layer instead)
- Old integration config clones without active product scope:
  - legacy bank WS helper duplicates (`ws_*`) unless actively used in new product
- Duplicated cari helper tables outside current product scope:
  - `uye_cari_hareket_fazla_para` and similar helpers should remain backlog until full finance module is introduced.

## 6. Final Decision

- Recommended schema changes:
  - implement P0 additions first (interest model, payment reference normalization, import lineage, legal event dictionary hardening).
  - then implement P1 relationship/lifecycle/reconciliation enrichments.
- Rejected/deferred changes:
  - full accounting/procurement/survey/e-commerce domains are out of current migration scope.
  - temp/cache/report-helper tables should not be mirrored in operational schema.
- Scope note:
  - Full accounting module is still out of scope.
  - However, legacy resident balance movement evidence (`uye_cari_hareket`) must be preserved.
  - Implement ledger-lite only for migration/reconciliation compatibility.
- What should be implemented next:
  1. P0 schema additions as focused patches
  2. controlled import **plan approval gate** refresh with updated mappings
  3. only then real import implementation patch set (chunked + idempotent + reversible)

