# CI4 SaaS Starter Kit (Foundation)

Bu proje, domain modullerden arindirilmis tekrar kullanilabilir CodeIgniter 4 SaaS foundation kitidir.

Korunan foundation bilesenleri:

- Auth + JWT access/refresh token
- RBAC + permission filter/policy
- Tenant context/tenant guard altyapisi
- Centralized API response + exception handling
- Audit log altyapisi
- List query/pagination utility
- Idempotency + rate-limit filtreleri
- OpenAPI/Swagger base dokumani
- Docker / CI / release readiness dokumantasyonu

Domain-specific moduller (company/department/branch/employee/course vb.) starter kitten kaldirilmistir.

## What is CodeIgniter?

CodeIgniter is a PHP full-stack web framework that is light, fast, flexible and secure.
More information can be found at the [official site](https://codeigniter.com).

This repository holds a composer-installable app starter.
It has been built from the
[development repository](https://github.com/codeigniter4/CodeIgniter4).

More information about the plans for version 4 can be found in [CodeIgniter 4](https://forum.codeigniter.com/forumdisplay.php?fid=28) on the forums.

You can read the [user guide](https://codeigniter.com/user_guide/)
corresponding to the latest version of the framework.

## Installation & updates

`composer create-project codeigniter4/appstarter` then `composer update` whenever
there is a new release of the framework.

When updating, check the release notes to see if there are any changes you might need to apply
to your `app` folder. The affected files can be copied or merged from
`vendor/codeigniter4/framework/app`.

## Setup

Copy `env` to `.env` and tailor for your app, specifically the baseURL
and any database settings.

## Important Change with index.php

`index.php` is no longer in the root of the project! It has been moved inside the *public* folder,
for better security and separation of components.

This means that you should configure your web server to "point" to your project's *public* folder, and
not to the project root. A better practice would be to configure a virtual host to point there. A poor practice would be to point your web server to the project root and expect to enter *public/...*, as the rest of your logic and the
framework are exposed.

**Please** read the user guide for a better explanation of how CI4 works!

## Repository Management

We use GitHub issues, in our main repository, to track **BUGS** and to track approved **DEVELOPMENT** work packages.
We use our [forum](http://forum.codeigniter.com) to provide SUPPORT and to discuss
FEATURE REQUESTS.

This repository is a "distribution" one, built by our release preparation script.
Problems with it can be raised on our forum, or as issues in the main repository.

## Server Requirements

PHP version 8.2 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - The end of life date for PHP 8.1 was December 31, 2025.
> - If you are still using below PHP 8.2, you should upgrade immediately.
> - The end of life date for PHP 8.2 will be December 31, 2026.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library

## Docker (Local Development)

Project root (`docker-compose.yml`) üzerinden calistirin:

1. Ornek env dosyasini inceleyin:
   - `backend/.env.docker.example`
2. Containerlari ayaga kaldirin:
   - `docker compose up --build -d`
3. Loglari takip edin:
   - `docker compose logs -f api`
4. API erisim:
   - `http://localhost:8080`
5. Swagger dokumani (API_DOCS_ENABLED=true ise):
   - `http://localhost:8080/docs`

Yararlı komutlar:

- Migration calistirma:
  - `docker compose exec api php spark migrate --all`
- Test calistirma:
  - `docker compose exec api vendor/bin/phpunit`
- Servisleri durdurma:
  - `docker compose down`
- Volume'lerle beraber sifirlama:
  - `docker compose down -v`

## CI Pipeline (GitHub Actions)

Workflow dosyasi: `.github/workflows/ci.yml`

CI adimlari:

- PHP 8.2 kurulumu (`setup-php`)
- Test icin MySQL service container
- Guvenli dummy degerlerle `.env` olusturma (JWT/DB vb.)
- `composer validate --strict`
- `composer install`
- Tum backend PHP dosyalarinda syntax check (`php -l`)
- Migration smoke test (`php spark migrate --all --no-header`)
- Test calistirma (`vendor/bin/phpunit`)

Tetikleme:

- Sadece `backend/**` ve workflow dosyasi degisikliklerinde calisir.

## Test Database Reset

Deterministik test state icin testleri calistirmadan once test DB'yi sifirlayin:

- `composer test:reset-db`
- `composer test -- --stop-on-failure`

`test:reset-db` sadece `CI_ENVIRONMENT=testing` veya `APP_ENV=testing` iken calisir ve
yalnizca `_test` ile biten DB adini resetler (ornek: `bys_test`).
Migrations test calisirken `DatabaseTestTrait` tarafindan fresh uygulanir.
Boylece development DB (`bys`) ve production DB korunur.

## Release Readiness

Production release readiness checklist ve smoke test plani:

- `docs/release_readiness_checklist.md`
