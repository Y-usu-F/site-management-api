# Final Legacy Schema Gap Verification (v2, Strict)

**Sources:** `sitebys.sql` (legacy), current migrations under `backend/app/Database/Migrations/`, prior narrative `backend/docs/legacy/final_legacy_gap_report.md`.

**Scope assumption:** Greenfield SaaS operation; **no legacy data import**. Verification asks whether the **current** relational model can represent the **business concepts the new product implements**, not whether every legacy table has a one-to-one mirror.

---

## 1. Executive Verdict

| Question | Answer |
|----------|--------|
| Is the current schema **sufficient** to represent the legacy system? | **MOSTLY** — sufficient for the operational domains implemented in this codebase (sites/units/residents, dues, payments, deposits, service requests, announcements, meters/consumption, assets/maintenance, visitors/security, staff, documents, governance/meetings, legal cases, notifications, common areas, etc.). **Not** sufficient for **literal parity** with legacy-only domains that were never modeled here (full cash/bank ledger, vendor/procurement, surveys, shopping cart, report/cache helpers). |
| Can the legacy system be **functionally represented without adding new tables**? | **Two readings:** (1) **Replacement product scope:** **YES** — existing tables cover those domains; nothing in Section 2 clears the bar for **mandatory** new tables. (2) **Entire `sitebys.sql` footprint:** **NO** — legacy modules such as `kasa_listesi`, `tedarikci_*`, `anket_*`, `uye_alisveris_sepeti` have no first-class home in the current schema **by design**. |
| Are “missing” legacy elements **critical or optional**? | For **no-import greenfield**, unmappable legacy areas are **optional / out of product scope**, not blockers. Anything that would matter only for **import fidelity** or **accounting-grade history** is **non-critical** under this task’s constraints. |

---

## 2. Real Missing Business Concepts (ONLY)

**None.**

Under strict gates (not representable in current schema **and** required for real-world operation **and** not indirectly supported **and** not “nice to have”), **no additive schema change is justified** while **no data import** is planned.

Notes (explicitly **not** Section 2 items):

- **Household / dependents (`uye_fert_tanimlari`):** Conceptually mappable using existing `resident_profiles`, `resident_contacts`, and `unit_occupancies` (`relationship_type`, dates) without a dedicated relation table; a separate normalized “household member” entity would be product polish, not a proven blocker here.
- **Per-resident ledger rows (`uye_cari_hareket`), cash book (`kasa_listesi`), bank mirror tables:** Accounting/reconciliation depth; excluded by **no accounting overdesign** and **no import**.
- **Asset checkout (`demirbas_zimmetler`):** Operational need can be approximated with `assets` (location/assignment context), `staff_assignments` / operational modules, and process metadata elsewhere; a dedicated checkout lifecycle table is **not** mandated under strict mode without a confirmed product requirement.

---

## 3. Fields That LOOK Missing But ARE ALREADY COVERED

Cross-check against normalized targets (avoid `txt*` resurrection in the target model).

| Legacy | Meaning / role | Current coverage |
|--------|----------------|------------------|
| `daire_tanimlari.txt1` (block name) | Block label | `blocks.name` (+ `units.block_id`) |
| `daire_tanimlari.txt2` | Unit number | `units.unit_no` |
| `daire_tanimlari.txt3` | Floor | `floors` + `units.floor_id` |
| `daire_tanimlari.txt4` | Unit type | `units.type` |
| `daire_tanimlari.txt5` | Gross m² | `units.gross_area` |
| `daire_tanimlari.txt6` | Net m² | `units.net_area` |
| `daire_tanimlari.txt7` | Land share | `units.land_share` (`AddLandShareToUnits`) |
| `daire_tanimlari.txt8` / `txt9` | Parking count / radiator count | Not first-class columns; **acceptable omission** for core billing/site ops, or future niche attributes only if product mandates (not suggested here). |
| `uye_tanimlari` name / phone / email columns (often `txt5`,`txt6`,`txt9`,`txt11`, …) | Identity & contact | `resident_profiles.first_name`, `last_name`, `phone`, `email`; extras via `resident_contacts` |
| `uye_tanimlari` national id (`txt7` pattern in mapping doc) | Identity | `resident_profiles.identity_number` (+ governance/privacy in app layer) |
| `uye_malik_bilgileri` / `onceki_kiracilar` | Occupancy periods | `unit_occupancies` (+ `source_type` / `source_legacy_id` from `AddMinimalMigrationSafeSchemaExtensions` when lineage matters) |
| `uye_arac_bilgisi` | Vehicles | `resident_vehicles` |
| Aidat / borç / tahsilat families | Dues and collections | `due_definitions`, `due_periods`, `due_batches`, `due_items`; `payments`, `payment_allocations`, `payment_events` |
| Delay interest / penalty amounts | Finance line items | `due_items.interest_amount`, `due_items.penalty_amount` |
| Online PSP / gateway trails | Provider events | `payment_events` (`provider`, `event_type`, `event_key`, `payload_json`) |
| Bank reconciliation grouping | Batch linkage hint | `payments.reconciliation_batch_id` |
| Deposit lifecycle | Deposits | `deposits`, `deposit_transactions` (+ reason codes on transactions migration patch) |
| Legal enforcement metadata | Case headers | `legal_cases` (+ `court_name`, `hearing_date`, `enforcement_stage_code` patch) |
| Cross-system identifiers during migration | Lineage | `legacy_id` on core entities (same migration patch); **no import → low urgency** |
| Rows that fail strict migration rules | Quarantine | `migration_quarantine_logs` |

---

## 4. Explicitly REJECTED Items

The following must **not** be added under this verification stance:

- **Raw `txt1`…`txtN` (or similar) columns** on operational tables — legacy encoding stays in legacy dump only.
- **Duplicate scalar fields** that duplicate `resident_profiles` / `units` / `payments` semantics “because legacy had another column.”
- **Full mirrors** of legacy ledger/accounting tables (`kasa_listesi`, full **double-entry** chart, resident **cari** mirror tables) — accounting-grade scope is out of bounds here.
- **Vendor/procurement** clones (`tedarikci_*`) unless the product formally owns procurement end-to-end.
- **Survey** (`anket_*`) and **e-commerce cart** (`uye_alisveris_sepeti`) artifacts — obsolete or separate product surface.
- **Staging/temp/report-helper** tables (`site_aktarim_temp`, `excel_aktarim_listesi`, cache/report tables) as operational schema.
- **Dynamic grab-bag “custom field” tables** for leftover legacy tails — governance-heavy; not justified without import pressure.

---

## 5. Final Decision

**A) Current schema is sufficient → NO CHANGE** for the **implemented multi-tenant site-management product** operating **without legacy import**.

Prior gaps identified in `final_legacy_gap_report.md` that touched schema have largely been addressed through additive patches (e.g. `due_items` interest/penalty amounts, `payments.reconciliation_batch_id`, occupancy source hints, legal header fields, `legacy_id`, quarantine logging). Remaining differences versus `sitebys.sql` are **scope boundaries** (accounting, procurement, surveys, commerce), not unresolved defects in the current model.

If the organization later **turns import back on** or mandates **accounting parity**, reassessment **outside strict greenfield mode** would be required — that is explicitly **not** part of this verdict.
