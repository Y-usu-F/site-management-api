# P0 Schema Patch Plan

Bu dokuman sadece P0 seviyesindeki sema iyilestirme planini tanimlar.
Bu asamada migration veya import implementasyonu yapilmaz.

## 1) Payment Reference Normalization

- target table: `payments`
- fields/table to add:
  - canonical `reference_no`
  - optional `reference_source` (e.g. `makbuz_no`, `ref_no`)
- reason:
  - legacy `tahsilat_listesi` icinde `makbuz_no` ve `ref_no` birlikte var
  - deterministic tek referans alani gerekli
- risk:
  - Medium (dedup/idempotency hatalari olusabilir)
- acceptance criteria:
  - tek canonical reference kurali yazili ve testli
  - duplicate/reference conflict durumlari quarantine kuralina bagli
- migration required: **yes**

## 2) Due Interest/Penalty Compatibility

- target table: `due_items` + supporting rules table
- fields/table to add:
  - interest/penalty rule modeli (`interest_rules` veya esitdeger)
  - due item bazli interest/penalty compatibility alanlari
- reason:
  - legacy `gecikme_faiz_oranlari` uyumlulugu gerekli
- risk:
  - High (monetary mismatch ve reconciliation sapmasi)
- acceptance criteria:
  - faiz/ceza kurallari deterministic
  - dry-run reconciliation farki tolerans icinde
- migration required: **yes**

## 3) Import Lineage Metadata

- target table: import lineage layer (new table or per-entity metadata extension)
- fields/table to add:
  - `import_run_id`
  - `source_legacy_table`
  - `source_legacy_id`
  - optional source hash/check columns
- reason:
  - kontrollu rollback, resume, audit ve izlenebilirlik
- risk:
  - Medium (lineage yoksa rollback ve root-cause zorlasir)
- acceptance criteria:
  - her import adayi run-id ile izlenebilir
  - resume/skip kurali lineage ile dogrulanir
- migration required: **yes**

## 4) Legal Event Dictionary Hardening

- target table: `legal_case_events` dictionary/lookup layer
- fields/table to add:
  - strict event type dictionary (legacy->normalized mapping destekli)
- reason:
  - `icra_takibi_hareketi` satirlari event/debt split acisindan belirsiz
- risk:
  - Medium (yanlis status/event semantigi)
- acceptance criteria:
  - tum legacy legal hareket tipleri dictionary'e bagli
  - unknown tipler quarantine'e gider
- migration required: **yes**

## 5) Ledger-Lite / Reconciliation Evidence Table

- target table: `resident_balance_movements` **veya** `finance_reconciliation_entries`
- fields/table to add (minimum):
  - `company_id`
  - `run_id`
  - `legacy_table`
  - `legacy_id`
  - `resident_legacy_ref`
  - `due_legacy_ref` (nullable)
  - `payment_legacy_ref` (nullable)
  - `movement_date`
  - `amount`
  - `direction` (debit/credit)
  - `description` / `payload_json`
- reason:
  - legacy `uye_cari_hareket` gecmis bakiye delili ve due/payment reconciliation icin kritik
  - full accounting scope acilmadan minimum kanit katmani gerekli
- risk:
  - High if ignored (historical balance mismatch)
- acceptance criteria:
  - resident bazli bakiye kaniti raporlanabilir
  - due/payment toplamlari ile reconcile edilebilir
  - full accounting modulu olmadan migration uyumlulugu saglanir
- migration required: **yes**

