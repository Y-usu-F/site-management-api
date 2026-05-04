# Legacy SiteBYS -> Yeni Sistem Mapping

Bu dokuman `sitebys.sql` icin **referans analizidir**. Dogrudan eski sema kullanimi hedeflenmez; veri migration ve alan eslestirme icin rehberdir.

## 1) Modullere Gore Legacy Tablo Gruplama

### Site / Blok / Daire
- `blok_tanimlari`
- `daire_tanimlari`
- `daire_tipi_tanimlari`
- `site_aktarim_temp`

### Uye / Sakin / Malik / Kiraci
- `uye_tanimlari`, `uye_tanimlari_onceki`
- `uye_malik_bilgileri`
- `uye_fert_tanimlari`
- `uye_durum_hareketi`
- `onceki_kiracilar`
- `uye_arac_bilgisi`
- `uye_yetkilendirme`, `yetkilendirme`, `uye_panel_tanimlar`

### Aidat / Borc / Tahsilat / Odeme
- `aidat_grup_tanimlari`, `aidat_listesi`, `aidat_iade_listesi`
- `borc_listesi`, `borc_iade_listesi`
- `tahsilat_listesi`, `odeme_listesi`, `uye_online_odemeler`
- `gecikme_faiz_oranlari`
- `excel_aktarim_listesi`, `bankadan_gelen_veriler`

### Kasa / Banka / Cari
- `kasa_banka_tanimlari`, `kasa_listesi`, `kasa_banka_transfer`
- `kasa_islem_gruplari`, `kasa_islem_turleri`
- `cari_yillar`, `cari_devir_hareketi`
- `uye_cari_hareket`, `uye_cari_hareket_fazla_para`
- `personel_cari_hareket`, `tedarikci_cari_hareket`
- `ws_banka_tanimlari`, `ws_teb_bilgileri`

### Sayaclar
- `elektrik_sayac`, `dogalgaz_sayac`
- `soguk_su_sayac`, `sicak_su_sayac`, `sicak_su_isitma_sayac`, `atik_su_sayac`

### Demirbas
- `demirbaslar`
- `demirbas_zimmetler`

### Talep / Sikayet
- `istek_sikayetler`
- `talepler`, `talep_durum_hareketi`, `talep_konu_tanimlari`, `talep_turu_tanimlari`

### Icra / Hukuk
- `icra_takibi2`
- `icra_takibi_hareketi`

### Depozito
- `depozito_listesi`

### Duyuru / SMS / Eposta
- `duyurular`, `duyuru_uyeler`
- `bildirimler`, `bildirim_secilen`
- `sms_ayarlari`, `sms_sablon_tanimlari`, `sms_secilen`, `sms_raporu`, `sms_kontor_bakiye`
- `eposta_ayarlari`, `eposta_sablon_tanimlari`, `eposta_secilen`, `eposta_dokuman_temp`, `gonderilen_epostalar`
- `cagrilar`, `cagrilar_secilen`

### Toplanti / Karar Defteri
- `genel_kurul_cagrisi`
- `karar_defteri`
- `ajanda`

### Personel
- `personel_tanimlari`
- `personel_cari_hareket`

### Evrak / Dosya
- `document_files`

### Diger / Etki Alanlari
- `abonelik_bilgileri`
- `anket_bilgileri`, `anket_konulari`, `anket_sorulari`, `anket_uygulamasi`, `anket_log`
- `bys_card`
- `fatura_gider_odeme_listesi`, `gelir_gruplari`, `gelir_tanimlari`, `genel_gelirler`, `genel_gelir_tanimlari`, `genel_giderler`, `gider_gruplari`, `gider_tanimlari`, `rapor_gelir`
- `not_bilgisi`
- `tedarikci_tanimlari`, `tedarikci_kategori_tanimlari`
- `toplu_secimler`, `ulke_tel_kodlari`
- `uye_alisveris_sepeti`

## 2) Eski Tablo -> Yeni Sistem Karsiliklari

Not: "Yeni tablo/modul" alani mevcut backend migrationlari baz alinip yazilmistir.

| Eski tablo | Yeni tablo/modul | Alan eslesmeleri (ornek) | Not |
|---|---|---|---|
| `blok_tanimlari` | `sites`, `blocks` (SITE-001) | `blok_adi/code` -> `blocks.name/code` | Site FK normalize edilir |
| `daire_tanimlari` | `units` (SITE-001) | `daire_no` -> `unit_no`; m2/tip -> `unit_type`,`net_area_m2` | Legacy serbest text alanlar normalize |
| `daire_tipi_tanimlari` | `units.unit_type` / lookup backlog | tip kodu -> lookup | Ayrik lookup tablo ihtiyaci olabilir |
| `site_aktarim_temp` | Migration staging | gecici alanlar -> ETL staging | Canli modele direkt alinmaz |
| `uye_tanimlari` | `resident_profiles` (SITE-002) | ad/soyad/tel/email -> profile/contact | Kimlik alanlari PII mask politikasi ister |
| `uye_tanimlari_onceki` | `resident_profiles` + archive staging | eski uye kaydi -> historical profile | Soft-delete/historic stratejisi |
| `uye_malik_bilgileri` | `unit_occupancies` (owner) | malik-daire iliskisi -> occupancy | `relationship_type=owner` |
| `onceki_kiracilar` | `unit_occupancies` (past) | kiraci gecmisi -> occupancy ended/passive | tarih araligi temizligi gerekli |
| `uye_fert_tanimlari` | `resident_profiles` iliski backlog | hane bireyleri -> resident relation | Household relation tablosu eksik |
| `uye_durum_hareketi` | audit/event backlog | durum gecmisi -> profile status events | Ayrik history tablo gerekebilir |
| `uye_arac_bilgisi` | `resident_vehicles` (SITE-002) | plaka/model -> vehicle alanlari | Plaka normalization |
| `uye_yetkilendirme` | RBAC (`users`,`roles`,`user_roles`) | yetki kodu -> permission/role | Legacy yetki semantiigi map gerektirir |
| `yetkilendirme` | RBAC permission catalog | kod/ad -> `permissions.code/name` | regex standardi uygulanir |
| `uye_panel_tanimlar` | profile preferences backlog | panel ayarlari -> user settings | Core modele alinmadi |
| `aidat_grup_tanimlari` | `due_definitions` (SITE-003) | grup/adi -> due definition | gelir-gider bagi backlog |
| `aidat_listesi` | `due_items` (SITE-003) | borc tutari/vade -> amount/due_date | period ve definition baglanir |
| `aidat_iade_listesi` | `payment_allocations`/refund backlog | iade tutar -> ters kayit | Net iade domaini backlog |
| `borc_listesi` | `due_items` | borc satiri -> due item | tip bazli mapping gerekir |
| `borc_iade_listesi` | refund/adjustment backlog | iade/duzeltme -> adjustment event | Ayrik due adjustment modeli yok |
| `tahsilat_listesi` | `payments`,`payment_allocations` (SITE-004) | tahsilat -> payment; borca dagitim -> allocation | provider/method normalize edilir |
| `odeme_listesi` | `payments` | odeme no/tarih/tutar -> payment alanlari | status semantic map gerekir |
| `uye_online_odemeler` | `payment_events` | gateway payload -> event | raw payload saklama stratejisi |
| `gecikme_faiz_oranlari` | due interest backlog | faiz oran -> kural engine | Mevcut due modulu faiz tablosu yok |
| `excel_aktarim_listesi` | ETL staging | aktarim satirlari | sadece migration araci |
| `bankadan_gelen_veriler` | `payment_events` / reconciliation backlog | banka satiri -> event | mutabakat modulu backlog |
| `kasa_banka_tanimlari` | finance account backlog | kasa/banka karti | mevcut schema'da yok |
| `kasa_listesi` | ledger backlog | kasa hareketi | double-entry yok |
| `kasa_banka_transfer` | transfer backlog | kaynak/hedef/tutar | yeni tablolar gerekli |
| `kasa_islem_gruplari` | lookup backlog | islem grup kodu | |
| `kasa_islem_turleri` | lookup backlog | islem turu | |
| `cari_yillar` | fiscal settings backlog | yil acilis/kapanis | |
| `cari_devir_hareketi` | opening balance backlog | devir tutarlari | |
| `uye_cari_hareket` | AR ledger backlog | uye cari hareket | due/payment ile reconcile |
| `uye_cari_hareket_fazla_para` | customer credit backlog | fazla odeme | kredi bakiyesi modeli eksik |
| `personel_cari_hareket` | HR-finance backlog | personel cari | |
| `tedarikci_cari_hareket` | AP ledger backlog | tedarikci cari | |
| `ws_banka_tanimlari` | integration config backlog | banka ws ayarlari | secrets vault gerekli |
| `ws_teb_bilgileri` | integration config backlog | banka ozel ayarlari | |
| `elektrik_sayac` | `meters` (SITE-009) | sayaç tip/seri -> meter | `meter_type=electricity` |
| `dogalgaz_sayac` | `meters` | -> meter | `meter_type=natural_gas` |
| `soguk_su_sayac` | `meters` | -> meter | `meter_type=cold_water` |
| `sicak_su_sayac` | `meters` | -> meter | `meter_type=hot_water` |
| `sicak_su_isitma_sayac` | `meters` | -> meter | type extension gerekebilir |
| `atik_su_sayac` | `meters` | -> meter | type extension |
| `demirbaslar` | `assets` (SITE-010) | demirbas no/ad/konum -> asset | depreciation alanlari backlog |
| `demirbas_zimmetler` | `asset_maintenance_records` kismi / assignment backlog | zimmet kisi/tarih | ayrik asset assignment tablosu gerek |
| `istek_sikayetler` | `service_requests` (SITE-005) | konu/aciklama/durum -> request | kategori mappingi gerekir |
| `talepler` | `service_requests` | -> request | paralel kaynak birlestirilir |
| `talep_durum_hareketi` | `service_request_comments` / event backlog | durum gecmisi | explicit status history backlog |
| `talep_konu_tanimlari` | `request_categories` | konu -> category | |
| `talep_turu_tanimlari` | `request_categories` type backlog | tur -> category dimension | |
| `icra_takibi2` | `legal_cases` (SITE-015) | dosya no/tur/durum -> legal_cases | state mapping kurali gerekir |
| `icra_takibi_hareketi` | `legal_case_events`,`legal_case_debts` | hareket tipi -> event/debt | islem tipine gore split |
| `depozito_listesi` | `deposits`,`deposit_transactions` (SITE-016) | depozito tutar/bakiye/iade -> deposit tx | transaction derivation gerekir |
| `duyurular` | `announcements` (SITE-007) | baslik/icerik/yayin -> announcement | hedefleme ayristirilir |
| `duyuru_uyeler` | `announcement_targets`,`announcement_reads` | hedef uye/okundu -> target/read | |
| `bildirimler` | `notification_messages` (SITE-006) | mesaj/kanal/durum | |
| `bildirim_secilen` | `notification_recipients` | alici secimi | |
| `sms_ayarlari` | `communication_providers` | sms provider config | secret maskeleme gerekir |
| `sms_sablon_tanimlari` | `notification_templates` | sms template | channel=sms |
| `sms_secilen` | `notification_recipients` | toplu secim | |
| `sms_raporu` | `notification_delivery_logs` | teslim raporu | provider status map |
| `sms_kontor_bakiye` | provider metrics backlog | kontor -> provider balance | |
| `eposta_ayarlari` | `communication_providers` | smtp config | secret + encryption |
| `eposta_sablon_tanimlari` | `notification_templates` | email template | |
| `eposta_secilen` | `notification_recipients` | alici secim | |
| `eposta_dokuman_temp` | `documents`,`document_versions` bridge backlog | eposta eki | gecici dosya temizligi |
| `gonderilen_epostalar` | `notification_messages`,`notification_delivery_logs` | gonderim kaydi | |
| `cagrilar` | `meetings` (SITE-014) | genel kurul cagrisi -> meeting publish | |
| `cagrilar_secilen` | `meeting_attendees` / target backlog | davetli listesi | |
| `genel_kurul_cagrisi` | `meetings` | toplanti cagrisi | |
| `karar_defteri` | `decision_book_entries` | karar no/metin/tarih | |
| `ajanda` | `meeting_agenda_items` | gundem maddesi | |
| `personel_tanimlari` | `staff_profiles` (SITE-012) | personel karti | |
| `document_files` | `documents`,`document_versions` (SITE-013) | dosya meta/path/checksum | binary/file store migration ayrik |
| `abonelik_bilgileri` | tenant/company settings backlog | paket/abonelik | billing modulu yok |
| `anket_bilgileri` | survey backlog | anket basligi | yeni modulde yok |
| `anket_konulari` | survey backlog | konu | |
| `anket_sorulari` | survey backlog | soru | |
| `anket_uygulamasi` | survey backlog | uygulama/yanit | |
| `anket_log` | survey backlog | log | |
| `bys_card` | card/payment token backlog | kart metadata | PCI gereksinimi |
| `fatura_gider_odeme_listesi` | expense/payment backlog | fatura odeme | AP modulu yok |
| `gelir_gruplari` | finance catalog backlog | gelir grup | |
| `gelir_tanimlari` | finance catalog backlog | gelir tanim | |
| `genel_gelirler` | general income backlog | gelir hareket | |
| `genel_gelir_tanimlari` | finance catalog backlog | | |
| `genel_giderler` | expense backlog | gider hareket | |
| `gider_gruplari` | expense catalog backlog | | |
| `gider_tanimlari` | expense catalog backlog | | |
| `rapor_gelir` | reporting mart backlog | rapor ozeti | hesaplanan veri |
| `not_bilgisi` | notes backlog | serbest not | entity bagi belirsiz |
| `tedarikci_tanimlari` | vendor backlog | tedarikci karti | procurement modulu yok |
| `tedarikci_kategori_tanimlari` | vendor category backlog | | |
| `toplu_secimler` | selection staging | toplu secim setleri | migration yardimci |
| `ulke_tel_kodlari` | reference data | ulke kodu | static seed |
| `uye_alisveris_sepeti` | e-commerce backlog | sepet | kapsam disi |

## 3) Yeni Sistemde Eksik Kalan Alanlar (Ozet)

- Faiz/ceza motoru (aidat gecikme faiz oranlari, otomatik faiz isletme)
- Uye/personel/tedarikci cari hesap ve mahsuplasma defteri
- Kasa/banka hesap kartlari ve transfer hareketleri
- Depozito iade kesinti detay kodlari (neden kodu, belge bagi)
- Asset zimmet (personel/daire bazli assignment life-cycle)
- Survey/anket modulu
- Genel gelir-gider ve tedarikci fatura odeme modulu
- Legacy not/etiket/serbest alanlarin normalize edilmis hedef modeli

