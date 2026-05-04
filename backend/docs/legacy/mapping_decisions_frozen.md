# Mapping Decisions Frozen

Bu dokuman, legacy migration oncesi kararlastirilmis mapping kurallarinin degismeyen referansidir.

## 1) due_definitions.calculation_type

- **Decision**
  - Canonical degerler: `fixed`, `area_based`, `manual`
  - Bilinmeyen kritik degerler: quarantine
- **Reason**
  - Due hesaplama davranisinin deterministic olmasi gerekir.
  - Uretim tarafinda yanlis tip fallback'i finansal sapma yaratir.
- **Affected Tables**
  - `due_definitions` (`calculation_type`)
  - Kaynak: legacy aidat/borc tanim tablolari
- **Acceptance Criteria**
  - Import edilen tum satirlarda `calculation_type` canonical set icinde.
  - Set disi her satir `migration_quarantine_logs` kaydi uretir.

## 2) Legal Event Split

- **Decision**
  - Odeme/tahsilat hareketleri: `legal_case_debts` veya payment-linked event kanalina ayrilir.
  - Not/durum/dokuman/mahkeme hareketleri: `legal_case_events` olarak tasinir.
  - Bilinmeyen legal movement tipi: quarantine.
- **Reason**
  - Hukuki akista borc hareketi ve operasyonel event ayrimi raporlama ve audit icin zorunlu.
- **Affected Tables**
  - `legal_cases`
  - `legal_case_debts`
  - `legal_case_events`
  - (bagli senaryoda) `payments`/payment event verisi
- **Acceptance Criteria**
  - Legal movement mapping sozlugunde tanimli her legacy tip tekil bir hedefe gider.
  - Unknown tipler import edilmez, quarantine'e duser.

## 3) Service Request Categories

- **Decision**
  - Import sirasinda kategori **otomatik olusturulmayacak**.
  - Izinli kategoriler dictionary'den pre-seed edilir.
  - Bilinmeyen kategori: quarantine.
- **Reason**
  - Serbest kategori acilimi veri kirlenmesi ve duplicate kategori riski uretir.
- **Affected Tables**
  - `request_categories`
  - `service_requests`
- **Acceptance Criteria**
  - Import oncesi pre-seed kategori listesi mevcut.
  - `service_requests.category_id` yalnizca seed edilen kategorilere baglanir.
  - Unknown kategori kodu gelen satirlar quarantine edilir.

## 4) Meter Type Enum Extension

- **Decision**
  - Izinli `meter_type` degerleri:
    - `electricity`
    - `cold_water`
    - `hot_water`
    - `wastewater`
    - `natural_gas`
    - `heat`
  - Unknown meter type: quarantine.
- **Reason**
  - Sayaç tipleri tuketim ve raporlamada dogrudan hesap davranisini etkiler.
- **Affected Tables**
  - `meters` (`meter_type`)
  - `meter_readings`
  - `consumption_reports`
- **Acceptance Criteria**
  - Hedefe yazilan tum meter satirlari izinli enum listesinde.
  - Enum disi degerler quarantine loguna yazilir.

## 5) Notification Provider Status

- **Decision**
  - `notification_messages.status` yalnizca normalized uygulama statulerini kullanir.
  - Provider'a ozel ham status `notification_delivery_logs.provider_status` alaninda saklanir.
  - Unknown message status: quarantine.
- **Reason**
  - Uygulama state machine'i provider bagimsiz kalmali, ham provider sinyali kaybolmamali.
- **Affected Tables**
  - `notification_messages` (`status`)
  - `notification_delivery_logs` (`provider_status`)
- **Acceptance Criteria**
  - Message status alaninda canonical app status seti disina cikilmaz.
  - Provider ham status degerleri delivery logda korunur.
  - Normalize edilemeyen statusler quarantine edilir.

