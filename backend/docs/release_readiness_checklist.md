# Release Readiness Checklist (FOUNDATION-010)

Bu dokuman, foundation katmaninin ilk production deploy oncesi minimum hazirlik kontrol listesini ve smoke test planini icerir.

## 1) Pre-Deployment Checklist

Her madde deploy oncesi "pass" olmalidir:

- [ ] **Env validation**
  - `APP_ENV`, `APP_BASE_URL`, `JWT_SECRET`, `JWT_ACCESS_TTL`, `JWT_REFRESH_TTL`, DB ayarlari dogru.
  - Uygulama boot sirasinda env validator hatasi vermiyor.
  - `JWT_SECRET` guclu ve production secret olarak ayarli.

- [ ] **Migration kontrolu**
  - Hedef ortamda migration dry-run planlandi.
  - `php spark migrate --all --no-header` basarili.
  - `audit_logs` tablosunda standart kolonlar mevcut (`request_id`, `occurred_at`, `old_values`, `new_values`, `ip_address`).

- [ ] **Test suite**
  - `composer validate --strict` basarili.
  - Unit/feature testler (en az foundation kritik testleri) basarili.

- [ ] **Permission matrix validation**
  - Catalog-DB-matrix uyumu dogrulandi.
  - Protected route coverage kontrolu basarili (permission filtresi eksik route yok).

- [ ] **Audit schema kontrolu**
  - Audit write testleri basarili.
  - Sensitive alan masking kontrol edildi.

- [ ] **OpenAPI erisim kontrolu**
  - `API_DOCS_ENABLED=true` iken `/docs` ve `/docs/openapi.yaml` erisilebilir.
  - Production politikasi geregi gerekiyorsa `API_DOCS_ENABLED=false` ile kapatildi.

- [ ] **Docker compose dogrulama**
  - `docker compose config` basarili.
  - `mysql` healthcheck passing.
  - `api` container DB hazir olmadan migrate calistirmiyor (entrypoint wait kontrolu).

## 2) Smoke Test Plan

Deploy sonrasi asagidaki senaryolar sirayla kosulur.

### 2.1 Login
- Endpoint: `POST /api/v1/auth/login`
- Beklenen:
  - HTTP 200
  - `success=true`
  - `data.access_token` dolu
  - `meta.request_id` mevcut

### 2.2 Refresh
- Endpoint: `POST /api/v1/auth/refresh`
- Beklenen:
  - HTTP 200
  - yeni access/refresh token doner
  - request_id mevcut

### 2.3 Me
- Endpoint: `GET /api/v1/auth/me` (Bearer token ile)
- Beklenen:
  - HTTP 200
  - dogru `user.id`, `company_id`
  - roller beklenen set ile uyumlu

### 2.4 Protected Route Permission
- Endpoint: permission korumali bir route (or. `GET /api/v1/companies`)
- Beklenen:
  - Yetkili kullanici: HTTP 200
  - Yetkisiz kullanici: HTTP 403 + `errors.error_code=FORBIDDEN`

### 2.5 Tenant Isolation
- Senaryo:
  - Tenant A token ile Tenant B kaydina erisim denemesi
- Beklenen:
  - HTTP 403
  - cross-tenant erisim engellenmis

### 2.6 Pagination Endpoint
- Endpoint: or. `GET /api/v1/employees?page=1&per_page=20`
- Beklenen:
  - HTTP 200
  - `data.meta.page`, `per_page`, `total`, `total_pages` alanlari var
  - envelope standardi korunmus

### 2.7 Audit Write
- Senaryo:
  - login, role assign/revoke veya entity update sonrasi audit kaydi kontrolu
- Beklenen:
  - `audit_logs` kaydi olusur
  - `action`, `company_id`, `actor_user_id`, `request_id`, `occurred_at` dolu
  - sensitive alanlar maskeli

## 3) Rollback Plan

Rollback adimlari, deploy tipine gore runbook'a eklenmelidir:

1. Yeni release trafigini durdur (LB/router seviyesinde onceki surume geri yonlendir).
2. Uygulama imajini/artefactini onceki stabil versiyona geri al.
3. Config revert:
   - Gerekli ise `API_DOCS_ENABLED` ve feature flag degerlerini onceki duruma cek.
4. Veritabani:
   - Geri alinabilir migration varsa kontrollu `migrate:rollback`.
   - Geri alinmasi riskli migration'larda DB backup restore proseduru uygula.
5. Post-rollback smoke:
   - login + me + kritik protected route + tenant isolation.
6. Olay kaydi:
   - incident notu, etkilenen sure, kok neden ve aksiyonlar.

## 4) Known Risks / Operational Notes

- PHP version drift: runtime PHP `8.2+` olmali.
- CI ve local ortam farklari (OS/driver) test sonucunu etkileyebilir.
- Permission catalog disi manuel DB permission insertleri matrix check'te fail/warn uretir.
- Production'da docs endpointi politika geregi kapatilmali olabilir.
- Seed islemleri ilk kurulum disinda kontrollu kosulmali (`ProductionSeeder` guard mantigi).

## 5) Minimum First Production Deploy Commands

Asagidaki komutlar ortam politikasina gore uyarlanarak uygulanir:

```bash
# 1) Dependency ve temel dogrulama
composer validate --strict
composer install --no-interaction --prefer-dist --no-progress --no-dev

# 2) Framework env kontrolu (boot-time validator ile)
php spark

# 3) Migration
php spark migrate --all --no-header

# 4) RBAC seed (ilk kurulum senaryosunda)
php spark db:seed ProductionSeeder

# 5) Hizmet saglik kontrolleri
curl -f http://127.0.0.1/health
curl -f http://127.0.0.1/ready
```

Not: Gercek ortamda secret degerler sadece guvenli secret management sistemi uzerinden verilmelidir.
