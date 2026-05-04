# Quarantine Usage Guide

`migration_quarantine_logs` tablosu, migration sirasinda otomatik duzeltilemeyen satirlari kaybetmeden izlemek icin kullanilir.

## 1) Ne Zaman Quarantine Yazilir?

Asagidaki durumlarda satir import edilmez, quarantine'e yazilir:

- Mapping dictionary'de olmayan kritik status/type degeri
- Zorunlu tarih alaninin parse edilememesi
- Parent bulunamayan child kaydi (orphan)
- Tenant veya scope dogrulamasi gecmeyen kayit
- Monetary tutarsizlik (hesaplanan bakiye != kaynak bakiye)
- Unique/dedup kurali ihlali ve otomatik cozumun guvensiz oldugu durum

## 2) Birinci Sinif Kolonlar

`migration_quarantine_logs` artik su operasyon kolonlarini birinci sinif alan olarak tutar:

- `run_id` (VARCHAR): migration run/batch kimligi
- `retry_count` (INT): satirin kac kez retry edildigi
- `resolved_at` (DATETIME): satir cozulduysa kapanis zamani
- `resolution_note` (TEXT): manuel cozum notu

`payload_json` kullanimi devam eder ve ham/normalize/context detayini tasir.

## 3) payload_json Onerilen Yapi

```json
{
  "entity_type": "due_item",
  "legacy_table": "aidat_listesi",
  "legacy_id": 12345,
  "raw_row": {
    "durum": "bilinmiyor",
    "vade_tarihi": "0000-00-00",
    "tutar": "150.50"
  },
  "normalized_attempt": {
    "status": null,
    "due_date": null,
    "amount": 150.50
  },
  "context": {
    "target_table": "due_items",
    "target_company_id": 42
  }
}
```

## 4) Ornek Senaryolar

### A) Invalid date
- **Hata:** `due_date=0000-00-00`
- **Aksiyon:** `error_message="invalid date format"`
- **Karar:** quarantine (zorunlu tarih alaniysa)

### B) Unknown status
- **Hata:** `durum='x-aktif'` dictionary'de yok
- **Aksiyon:** `error_message="unknown status mapping: x-aktif"`
- **Karar:** quarantine (critical enum)

### C) Orphan unit
- **Hata:** occupancy satiri var ama hedef `unit_id` bulunamadi
- **Aksiyon:** `error_message="orphan unit reference"`
- **Karar:** quarantine

### D) Payment mismatch
- **Hata:** source `tahsilat=100`, allocations toplam `80`
- **Aksiyon:** `error_message="payment allocation mismatch"`
- **Karar:** quarantine + manual reconciliation

## 5) Quarantined Satirlar Nasil Incelenir?

1. `entity_type` ve `legacy_table` bazli grupla.
2. `error_message` patternlerine gore issue classlari cikar.
3. Her class icin cozum stratejisi belirle:
   - dictionary guncellemesi
   - kaynak veri temizligi
   - manuel map
4. Cozulen satirlarda `resolved_at` ve `resolution_note` alanlarini doldur.
5. Yeniden denenecek satirlarin `retry_count` degerini arttir.

## 6) Retry Stratejisi

- Retry toplu ama kontrollu yapilmali (entity bazli, kucuk batch).
- Ayni satir tekrar quarantine olursa `retry_count` kolonu artir.
- 3+ tekrar eden satirlar manual review kuyruğuna kalici alinmali.

## 7) SQL Inceleme Ornekleri

```sql
-- En cok hata veren tablo
SELECT legacy_table, COUNT(*) AS cnt
FROM migration_quarantine_logs
GROUP BY legacy_table
ORDER BY cnt DESC;
```

```sql
-- Belirli run_id icin unresolved satirlar
SELECT id, run_id, entity_type, legacy_table, legacy_id, retry_count, error_message, created_at
FROM migration_quarantine_logs
WHERE run_id = '2026-05-01T00:00:00Z#batch-01'
  AND resolved_at IS NULL
ORDER BY id DESC;
```

```sql
-- Retry count >= 3 olan blocker satirlar
SELECT id, entity_type, legacy_table, legacy_id, retry_count, error_message
FROM migration_quarantine_logs
WHERE retry_count >= 3
  AND resolved_at IS NULL
ORDER BY retry_count DESC, id DESC;
```

