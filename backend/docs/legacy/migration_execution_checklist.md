# Migration Execution Checklist

Bu checklist import komutu degil; kontrollu migration operasyonu icin rehberdir.

## 1) Pre-Migration Backup Checklist

- Kaynak DB (`sitebys`) full backup alindi ve checksum dogrulandi.
- Hedef DB full backup/snapshot alindi.
- Backup restore smoke-test en az bir ortamda calistirildi.
- Migration dictionary freeze edildi (`legacy_migration_dictionary.md`).
- `mapping_decisions_frozen.md` onayi alindi.
- Import user yetkileri read-only (source) + controlled write (target) olarak sinirlandi.
- Zaman damgasi ve audit kaydi icin run-id tanimlandi.

## 2) Dry-Run Checklist

- Dry-run ortami production ile ayni schema versiyonunda.
- Dry-run sadece orneklem degil, mumkunse full dataset ile calisti.
- Import oncesi `php spark legacy:dry-run-analyze --scope=all` calistirildi.
- Dry-run JSON cikti dosyasi kalici olarak kaydedildi.
- Quarantine oranlari raporlandi (entity bazli).
- Unknown status/type count raporlandi.
- Duplicate/constraint hatalari listelendi.
- Reconciliation raporlari (count/tutar) cikartildi.
- Blocker listesi ayrintili olarak gozden gecirildi.
- Quarantine candidate listesi manuel triage icin ayrildi.
- Monetary warningler (tahsilat/borc/depozito) dogrulandi.
- `run_id` release sorumlulari tarafindan imzali/onayli kayda alindi.

## Dry-Run Reporting Gate

- `dry_run_reporting.md` uzerindeki tum rapor tanimlari calistirildi.
- `sql/dry_run_reports.sql` sorgulari run-id bazli icra edildi ve arşivlendi.
- `dry_run_acceptance_criteria.md` GO/NO-GO maddeleri tek tek degerlendirildi.
- Raporlarin ozet sonucu release karar kaydina eklendi.

## 3) Data Cleanup Checklist (Legacy)

- `0000-00-00*` tarih degerleri `NULL` veya business fallback ile duzeltildi.
- `txt1..txtN` alanlari mapping tablosuna tasnif edildi.
- Karakter seti/encoding uyumsuz satirlar normalize edildi.
- Numeric alanlardaki metin degerler temizlendi.
- Orphan kayitlar (parent olmayan child) isaretlendi.
- Duplicate identity alanlari (site/unit/resident vb.) deduplicate planina alindi.

## 4) Import Order

1. Foundation referanslar (company/user/role/permission)
2. Mekansal model (`sites`, `blocks`, `floors`, `units`)
3. Kisi/occupancy (`resident_profiles`, `unit_occupancies`, contact/vehicle)
4. Due model (`due_definitions`, `due_periods`, `due_items`)
5. Payments (`payments`, `payment_allocations`, `payment_events`)
6. Deposits (`deposits`, `deposit_transactions`)
7. Requests/work orders
8. Meters/readings/reports
9. Documents
10. Governance (meetings/agenda/attendee/decision_book)
11. Legal cases + debts/events/documents
12. Notification data

## 5) Reconciliation Checks

- Satir sayisi: source->target entity bazli
- Toplam borc/tahsilat/depozito bakiyesi farki
- Paid/unpaid status dagilimi tutarliligi
- Tenant bazli kayit sayisi kontrolu
- Legacy->new `legacy_id` map uniqueness kontrolu
- Quarantine toplam/adet ve sebep dagilimi

## 6) Go / No-Go Rules

### Go
- P0 entitylerde kritik hata yok
- Reconciliation farki tolerans icinde
- Unknown mapping oranlari kabul edilebilir seviyede
- Quarantine queue elle gozden gecirildi
- Quarantine resolved/unresolved raporu uretilmis ve onaylanmis

### No-Go
- Cross-tenant karisiklik tespit edilirse
- Monetary reconciliation ciddi sapma verirse
- Orphan oranlari esitlenen limitin ustundeyse
- Rollback test edilmemisse
- `retry_count >= 3` unresolved satir varsa

## 7) Rollback Strategy

- Her migration run icin run-id ve batch tag kullan.
- Problemde:
  1. Yeni importu durdur
  2. Hedef DB snapshot restore et
  3. Quarantine + loglardan root cause cikar
  4. Dictionary/transform fix et
  5. Dry-run tekrar etmeden production rerun yapma

## 8) Manual Review Queue

Manual review'a dusurulecek minimum durumlar:

- Unknown enum/status/type degerleri
- Parent bulunamayan child kayitlar
- Tarih parse edilemeyen kritik kayitlar
- Monetary mismatch (tahsilat/borc/depozito)
- Cakisan `legacy_id` veya duplicate natural key
- Kimlik/iletisim alaninda guvenlik/PII anomalileri
- `retry_count >= 3` olan unresolved quarantine satirlari

