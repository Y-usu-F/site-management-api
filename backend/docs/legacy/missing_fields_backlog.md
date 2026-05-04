# Missing Fields Backlog (Legacy Gap -> Yeni Sistem)

Bu backlog migration oncesi teknik borc/eksik alan gorunurlugu icindir. Dogrudan tablo degisikligi yapilmaz; once analiz ve onceliklendirme yapilir.

## P0 - Veri Migration Icin Kritik

| Baslik | Eksik/Konu | Etkilenen modul |
|---|---|---|
| Legacy->New ID mapping tablolari | Her ana varlik icin `legacy_id_map` stratejisi yok | Tum migration |
| Kasa/Banka/Cari cekirdek modeli | Legacy cari hareketlerinin yeni sistemde dogrudan karsiligi sinirli | Finans |
| Due faiz/ceza modeli | `gecikme_faiz_oranlari` karsiligi eksik | Aidat/Borc |
| Depozito neden kodlari | refund/deduction icin reason code standardi yok | Deposit |
| Legal hareket tip sozlugu | `icra_takibi_hareketi` -> event/debt split sozlugu eksik | Hukuk |
| Data quarantine tablosu | Parse edilemeyen satirlar icin standart hata havuzu yok | ETL |
| Reconciliation raporu | Tahsilat/borc devir farklarini dogrulayacak standart rapor eksik | Finans |

## P1 - Urun Degeri Yuksek

| Baslik | Eksik/Konu | Etkilenen modul |
|---|---|---|
| Asset zimmet life-cycle | `demirbas_zimmetler` icin ayrik model yok | Demirbas |
| Resident household relations | `uye_fert_tanimlari` karsiligi eksik | Resident |
| Status history tablolari | request/resident gibi varliklarda explicit durum gecmisi | Coklu modul |
| Notification consent/preferences | hedefli iletisimde izin yonetimi eksik | Iletisim |
| Vendor/procurement modeli | tedarikci + fatura odeme akislarinda gap | Finans/Operasyon |
| Survey/anket modulu | anket tablolarina karsilik yok | Community |

## P2 - Ileri Seviye / Opsiyonel

| Baslik | Eksik/Konu | Etkilenen modul |
|---|---|---|
| Dynamic custom fields | `txt1..txtN` yerine kontrollu custom field framework | Tum modul |
| Multi-currency detaylari | TRY disi para birimi kur/fark yonetimi | Finans |
| Data lineage metadata | satir bazli kaynak/transform izleme | ETL |
| Legacy rapor kopyalari | `rapor_gelir` benzeri hazir raporlarin birebir kopyasi | BI |
| Sepet/e-ticaret artifaktlari | `uye_alisveris_sepeti` kapsam disi | Opsiyonel |

## Modul Bazli Eksik Alan Onerileri

### Site/Resident
- Occupancy `source_type`, `source_legacy_id`
- Resident relation (spouse/child/dependent) yapisi

### Due/Payment
- `interest_amount`, `penalty_amount` explicit alanlari (kural motoru ile)
- payment reconciliation batch kimligi

### Deposit
- `refund_reason_code`, `deduction_reason_code`
- transaction-level attachment/document baglantisi

### Legal
- `court_name`, `hearing_date`, `enforcement_stage_code` gibi migration alanlari

### Documents
- legacy file integrity (`legacy_checksum`, `legacy_path`) metadata

## Kabul Kriteri

- P0 backlog maddeleri planlanmadan bulk migration baslatilmaz.
- P1 maddeleri UAT oncesi sprint planina alinmali.
- P2 maddeleri release sonrasi iyilestirme havuzunda takip edilir.

