# Dry-Run Reporting

Bu dokuman, import calistirmadan once/sonra sadece raporlama amacli dry-run kontrol tanimlarini listeler.

## 1) Quarantine Summary by run_id

- Amac: Her dry-run batch'in hata profili ve hacmini gormek.
- Boyutlar:
  - `run_id`
  - toplam quarantine satiri
  - resolved / unresolved sayisi
  - ortalama `retry_count`
- Cikti: Batch bazli kalite trendi.

## 2) Unresolved Quarantine Rows

- Amac: Cozulmemis satirlari operasyon kuyruğuna cikarmak.
- Filtre:
  - `resolved_at IS NULL`
  - opsiyonel `run_id`
- Cikti kolonlari:
  - `entity_type`, `legacy_table`, `legacy_id`, `error_message`, `retry_count`, `created_at`

## 3) retry_count >= 3 Blockers

- Amac: Tekrarlayan ve manuel karar gerektiren satirlari tespit etmek.
- Kural:
  - `retry_count >= 3`
  - `resolved_at IS NULL`
- Cikti: Go/No-Go blocker listesi.

## 4) Unknown Mapping Distribution

- Amac: Dictionary kapsami disindaki degerleri dagilimli gormek.
- Tespit paterni:
  - `error_message LIKE '%unknown%'`
  - `error_message LIKE '%mapping%'`
- Gruplama:
  - `entity_type`, `legacy_table`, `error_message pattern`

## 5) Orphan Reference Distribution

- Amac: Parent-child baglantisi kopuk satirlari olcmek.
- Tespit paterni:
  - `error_message LIKE '%orphan%'`
  - `error_message LIKE '%reference%'`
- Gruplama:
  - `entity_type`, `legacy_table`

## 6) Monetary Mismatch Distribution

- Amac: Finansal tutarsizliklari isolate etmek.
- Tespit paterni:
  - `error_message LIKE '%mismatch%'`
  - `error_message LIKE '%amount%'`
  - `error_message LIKE '%balance%'`
- Cikti:
  - entity bazli mismatch sayisi
  - run bazli mismatch trendi

## 7) legacy_id Duplicate Checks

- Amac: Hedef tablolarda `legacy_id` tekilligini kontrol etmek.
- Kapsam (ornek cekirdek):
  - `sites`, `blocks`, `units`, `resident_profiles`, `unit_occupancies`
  - `due_items`, `payments`, `deposits`, `service_requests`, `legal_cases`
- Kural:
  - `legacy_id IS NOT NULL`
  - `GROUP BY legacy_id HAVING COUNT(*) > 1`

## 8) Entity-Level Source/Target Count Comparison

- Amac: Kaynak-hedef satir sayi sapmalarini gormek.
- Yaklasim:
  - Source count: legacy tablo sayilari (ayri rapordan veya staging snapshot'tan)
  - Target count: `legacy_id IS NOT NULL` sayisi
- Cikti:
  - `entity_type`, `source_count`, `target_count`, `delta`

## 9) Go/No-Go Summary

- Amac: Dry-run kabul kriterlerini tek sayfada ozetlemek.
- Minimum metrikler:
  - unresolved P0 quarantine count
  - `retry_count >= 3` blocker count
  - duplicate `legacy_id` count
  - cross-tenant issue count
  - monetary mismatch count/tolerance

## Raporlama Calisma Sirasi (Oneri)

1. `run_id` bazli summary
2. unresolved queue
3. blocker queue (`retry_count >= 3`)
4. unknown/orphan/mismatch dagilimlari
5. duplicate `legacy_id` taramasi
6. source/target count karsilastirmasi
7. go/no-go summary

