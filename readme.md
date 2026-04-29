# CI4 SaaS Starter Kit

Bu klasor, domain-specific modullerden arindirilmis reusable CodeIgniter 4 SaaS foundation projesidir.

## Foundation kapsaminda kalanlar

- Auth (login, refresh, logout, me, forgot/reset password)
- JWT access/refresh token altyapisi
- RBAC (permission catalog, permission filter, policy, matrix validation)
- Tenant context ve tenant guard altyapisi
- Audit log standardi ve taxonomy
- API response standardi ve global exception handling
- Pagination/list query utility
- Idempotency ve rate-limit filtreleri
- Swagger/OpenAPI foundation endpointleri
- Docker local gelistirme dosyalari
- GitHub Actions CI
- Release readiness dokumani

## Domain-specific moduller

Asagidaki moduller foundationdan cikarilmistir:

- Company
- Department
- Branch
- Employee / EmployeeImport
- Course / CourseCategory / CourseModule
- Lesson / LessonMedia
- CompanySettings

## Dokumantasyon

- Backend README: `backend/README.md`
- OpenAPI: `backend/app/Docs/openapi.yaml`
- Release checklist: `backend/docs/release_readiness_checklist.md`
- Cleanup review list: `backend/docs/review_required_cleanup.md`
