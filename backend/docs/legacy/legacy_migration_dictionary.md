# Legacy Migration Dictionary

Bu dokuman legacy `sitebys.sql` degerlerinin yeni sistemde normalize edilmesi icin referans sozluktur.
Amaç: ETL sırasında deterministic donusum + bilinmeyen degerleri quarantine'e atma karari.

## 1) Occupancy Relationship / Status

| legacy_value | normalized_value | target_table | target_column | fallback_rule | quarantine_if_unknown |
|---|---|---|---|---|---|
| malik | owner | unit_occupancies | relationship_type | trim/lowercase sonra map | yes |
| ev_sahibi | owner | unit_occupancies | relationship_type | `owner` | yes |
| kiraci | tenant | unit_occupancies | relationship_type | `tenant` | yes |
| tenant | tenant | unit_occupancies | relationship_type | `tenant` | no |
| aile | family_member | unit_occupancies | relationship_type | `family_member` | yes |
| aktif | active | unit_occupancies | status | trim/lowercase sonra map | yes |
| pasif | passive | unit_occupancies | status | `passive` | no |
| cikti | passive | unit_occupancies | status | `passive` | no |
| ended | passive | unit_occupancies | status | `passive` | no |

## 2) Due Item Types / Statuses

| legacy_value | normalized_value | target_table | target_column | fallback_rule | quarantine_if_unknown |
|---|---|---|---|---|---|
| aidat | fixed | due_definitions | calculation_type | bilinmeyen tipte `fixed` | yes |
| sabit | fixed | due_definitions | calculation_type | `fixed` | no |
| metrekare | area_based | due_definitions | calculation_type | `area_based` | yes |
| manuel | manual | due_definitions | calculation_type | `manual` | no |
| odenmedi | unpaid | due_items | status | trim/lowercase sonra map | yes |
| kismi | partial | due_items | status | `partial` | no |
| odendi | paid | due_items | status | `paid` | no |
| iptal | cancelled | due_items | status | `cancelled` | no |

## 3) Payment Methods / Statuses

| legacy_value | normalized_value | target_table | target_column | fallback_rule | quarantine_if_unknown |
|---|---|---|---|---|---|
| nakit | cash | payments | method | bos/unknown ise `cash` | yes |
| banka | bank_transfer | payments | method | `bank_transfer` | yes |
| havale | bank_transfer | payments | method | `bank_transfer` | no |
| kredi_karti | credit_card | payments | method | `credit_card` | no |
| online | online | payments | method | `online` | no |
| tamamlandi | completed | payments | status | default `completed` | yes |
| basarili | completed | payments | status | `completed` | no |
| bekliyor | pending | payments | status | `pending` | no |
| iptal | cancelled | payments | status | `cancelled` | no |
| iade | refunded | payments | status | `refunded` | no |

## 4) Deposit Transaction Types / Statuses

| legacy_value | normalized_value | target_table | target_column | fallback_rule | quarantine_if_unknown |
|---|---|---|---|---|---|
| alim | receive | deposit_transactions | transaction_type | trim/lowercase sonra map | yes |
| teslim_alindi | receive | deposit_transactions | transaction_type | `receive` | no |
| iade | refund | deposit_transactions | transaction_type | `refund` | no |
| kesinti | deduction | deposit_transactions | transaction_type | `deduction` | no |
| borca_mahsup | apply_to_debt | deposit_transactions | transaction_type | `apply_to_debt` | no |
| iptal | cancel | deposit_transactions | transaction_type | `cancel` | no |
| aktif | active | deposits | status | default create: `active` | yes |
| kismi_iade | partially_refunded | deposits | status | `partially_refunded` | no |
| iade_edildi | refunded | deposits | status | `refunded` | no |
| borca_mahsup | applied_to_debt | deposits | status | `applied_to_debt` | no |
| iptal | cancelled | deposits | status | `cancelled` | no |

## 5) Legal Case Statuses / Event Types

| legacy_value | normalized_value | target_table | target_column | fallback_rule | quarantine_if_unknown |
|---|---|---|---|---|---|
| taslak | draft | legal_cases | status | trim/lowercase sonra map | yes |
| hazirlandi | prepared | legal_cases | status | `prepared` | no |
| avukata_gonderildi | sent_to_lawyer | legal_cases | status | `sent_to_lawyer` | no |
| dosyalandi | filed | legal_cases | status | `filed` | no |
| takipte | in_progress | legal_cases | status | `in_progress` | no |
| odendi | paid | legal_cases | status | `paid` | no |
| kapandi | closed | legal_cases | status | `closed` | no |
| iptal | cancelled | legal_cases | status | `cancelled` | no |
| not | note | legal_case_events | event_type | bilinmeyende `note` | yes |
| tebligat | notice_sent | legal_case_events | event_type | `notice_sent` | no |
| dosya_acildi | filed | legal_case_events | event_type | `filed` | no |
| odeme | payment_received | legal_case_events | event_type | `payment_received` | no |
| durum | status_changed | legal_case_events | event_type | `status_changed` | no |

## 6) Service Request Statuses / Categories

| legacy_value | normalized_value | target_table | target_column | fallback_rule | quarantine_if_unknown |
|---|---|---|---|---|---|
| yeni | open | service_requests | status | trim/lowercase sonra map | yes |
| acik | open | service_requests | status | `open` | no |
| atandi | assigned | service_requests | status | `assigned` | no |
| cozuldu | resolved | service_requests | status | `resolved` | no |
| kapandi | closed | service_requests | status | `closed` | no |
| iptal | cancelled | service_requests | status | `cancelled` | no |
| sikayet | complaint | request_categories | code/name | category yoksa dinamik create kuyruğu | yes |
| ariza | fault | request_categories | code/name | `fault` | yes |
| teknik | technical | request_categories | code/name | `technical` | yes |
| temizlik | cleaning | request_categories | code/name | `cleaning` | yes |

## 7) Meter Types

| legacy_value | normalized_value | target_table | target_column | fallback_rule | quarantine_if_unknown |
|---|---|---|---|---|---|
| elektrik | electricity | meters | meter_type | trim/lowercase sonra map | yes |
| su_soguk | cold_water | meters | meter_type | `cold_water` | no |
| su_sicak | hot_water | meters | meter_type | `hot_water` | no |
| su_atik | wastewater | meters | meter_type | `wastewater` | yes |
| dogalgaz | natural_gas | meters | meter_type | `natural_gas` | no |
| isitma | heat | meters | meter_type | `heat` | yes |

## 8) Notification Channels / Statuses

| legacy_value | normalized_value | target_table | target_column | fallback_rule | quarantine_if_unknown |
|---|---|---|---|---|---|
| sms | sms | notification_messages | channel | trim/lowercase sonra map | no |
| eposta | email | notification_messages | channel | `email` | no |
| email | email | notification_messages | channel | `email` | no |
| push | push | notification_messages | channel | `push` | yes |
| hazir | queued | notification_messages | status | bilinmeyende `queued` | yes |
| kuyruk | queued | notification_messages | status | `queued` | no |
| gonderildi | sent | notification_messages | status | `sent` | no |
| basarisiz | failed | notification_messages | status | `failed` | no |
| iptal | cancelled | notification_messages | status | `cancelled` | no |
| teslim | delivered | notification_delivery_logs | provider_status | oldugu gibi sakla | no |

## Genel Kurallar

- `legacy_value` map oncesi: trim + lowercase + turkce karakter normalize (opsiyonel).
- Bos degerlerde fallback uygulanir; zorunlu alanda fallback yoksa quarantine.
- Quarantine satiri yazarken `entity_type`, `legacy_table`, `legacy_id`, ham payload, hata sebebi zorunludur.

