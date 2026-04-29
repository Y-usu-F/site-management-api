# Review Required (FOUNDATION-011)

Asagidaki dosyalar domain module degil, foundation tenant/auth altyapisinin parcasi oldugu icin otomatik silinmedi:

- `app/Database/Seeds/BaseAppSeeder.php`
  - `ensureSystemCompany()` metodu tenant bootstrap icin system company kaydi uretiyor.
- `app/Database/Migrations/2026-04-25-100000_CreateCoreTables.php`
  - `companies` tablosu tenant boundary (`company_id`) icin cekirdek yapida kullaniliyor.
- `app/Config/TenantConfig.php`
  - tenant context/super admin davranis ayarlari bulunuyor.

Not:
- Eger tamamen company kavramindan bagimsiz bir tenant stratejisine gecilecekse,
  bu dosyalar mimari karar ile yeniden tasarlanmalidir.
