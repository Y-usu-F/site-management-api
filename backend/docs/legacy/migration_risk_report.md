# Legacy Migration Risk Report

Kaynak: `sitebys.sql` statik analiz.

## 1) Tespit Edilen Kotu Pratikler

- **MyISAM kullanimi:** 97 tablo
  - Transaction/FK destegi olmadigi icin veri butunlugu riski yuksek.
- **FK/constraint yoklugu:** `FOREIGN KEY` / `CONSTRAINT` tespiti yok
  - Parent-child iliskileri uygulama koduna birakilmis.
- **`txt1/txt2/...` benzeri serbest alanlar:** 516 adet kolon paterni
  - Anlamsal belirsizlik, migration mapping maliyeti yuksek.
- **`0000-00-00` veya `0000-00-00 00:00:00`:** 189 kayit
  - Yeni sistemde gecerli tarih formatina donusum gerekli.
- **`utf8mb3` / `CHARSET=utf8`:** 200 tespit
  - Karakter seti modern standarda (`utf8mb4`) cekilmeli.
- **Enum/string daginikligi:**
  - Durum/tip alanlarinda standart kod listesi yok, cok sayida varyant riski var.
- **Tarih/para tutarsizligi:**
  - Tarih alanlari metin/nullable/zero-date karisikligi.
  - Para alanlarinda decimal standardizasyonu ihtiyaci.

## 2) Modul Bazli Risk Degerlendirmesi

| Modul | Risk | Gerekce |
|---|---|---|
| Site/Unit/Resident | Orta | Kimliklestirme ve occupancy gecmisinde duplicate/format riski |
| Due/Payment | Yuksek | Borc-tahsilat baglantilari FK'siz, mahsuplasma zinciri dağinik |
| Kasa/Banka/Cari | Cok Yuksek | Yeni sistemde birebir karsilik moduller henuz tam yok |
| Meter | Orta | Sayac tip normalizasyonu ve period baglari gerekli |
| Asset | Orta | Zimmet life-cycle eksik model gerektiriyor |
| Request/Complaint | Dusuk-Orta | Kategori/durum map edilirse tasima kolay |
| Legal | Orta | Dosya/hareket tip donusumu dikkat ister |
| Deposit | Orta | Islem tipleri ve bakiye turetimi kuralla donusturulmeli |
| Notification | Orta | Provider/template payload temizligi ve secret ayrimi gerekir |
| Governance | Dusuk-Orta | Cagri/karar/ajanda map'i net |
| Staff | Orta | Personel + cari baglantisi ayrisiyor |
| Document | Orta | Dosya yolu/versiyon/checksum standardizasyonu gerekir |

## 3) Migration Sirasi (Oneri)

1. **Foundation + Referans Veriler**
   - company, users, roles, permissions, ulke/tip lookuplar.
2. **SITE-001 + SITE-002 (Mekansal ve Kisi Modeli)**
   - sites/blocks/floors/units
   - resident_profiles, occupancies, contacts, vehicles
3. **SITE-003 + SITE-004 (Aidat/Borc/Tahsilat)**
   - due_definitions/periods/items
   - payments/payment_allocations/payment_events
4. **SITE-016 (Depozito)**
   - deposits, deposit_transactions (due/payment ile capraz kontrol)
5. **SITE-005 (Talep/Is Emri)**
6. **SITE-009 (Sayaç/Tuketim)**
7. **SITE-010 (Demirbas/Bakim)**
8. **SITE-006 + SITE-007 (Iletisim/Duyuru)**
9. **SITE-014 (Toplanti/Karar)**
10. **SITE-012 (Personel)**
11. **SITE-013 (Evrak/Dosya)**
12. **SITE-015 (Hukuk/Icra)**
13. **Kapsam Disi Finans Modulleri (Kasa/Banka/Cari/Genel Gelir-Gider)**

## 4) Donusum ve Temizlik Kurallari

- **Kimlik alanlari**
  - Legacy integer PK -> yeni sistem PK + `public_id` stratejisi.
- **Tarih alanlari**
  - `0000-00-00*` -> `NULL` + gerekirse `event_note`.
  - Metin tarih -> `DATETIME` parse + invalid satir quarantine.
- **Para alanlari**
  - Tum tutarlar `DECIMAL(12,2)` normalize.
  - Currency bos ise `TRY` fallback (is kuralina gore).
- **Durum/tip alanlari**
  - Legacy serbest metin -> enum/kod tablosu (mapping dictionary).
- **Text blob / txtN alanlari**
  - Domain alanlarina parcala; karsiligi olmayanlar `notes/meta_json`.
- **Iletisim/secret alanlari**
  - SMTP/SMS API anahtarlari migration sirasinda sifrelenmeli, audit-mask uygulanmali.
- **Duplicate kayitlar**
  - site+block+unit, unit+resident+status, due item unique kurallarina gore dedup.

## 5) Veri Kalitesi Kapilari (Go/No-Go)

- P0 entitylerde orphan oranı < %0.5
- Zorunlu alan doluluk > %99
- Date parse hata oranı < %1
- Monetary reconciliation farki <= tolerans (manuel onayli)
- Cross-tenant sizinti testi: 0

