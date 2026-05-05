# Frontend Bulk / Import / Export Contract

Bu dokuman, frontend tarafinda eklenen bulk action + Excel import/export UI kontratini tanimlar.

## Beklenen endpointler

### Sites
- `DELETE /api/v1/sites/bulk`
  - Body: `{ "ids": [1,2,3] }`
- `GET /api/v1/sites/export`
- `POST /api/v1/sites/import` (`multipart/form-data`, `file`)
- `GET /api/v1/sites/import-template`

### Blocks
- `DELETE /api/v1/blocks/bulk`
  - Body: `{ "ids": [1,2,3] }`
- `GET /api/v1/blocks/export?site_id=:siteId`
- `POST /api/v1/blocks/import` (`multipart/form-data`, `file`)
- `GET /api/v1/blocks/import-template`

### Floors
- `DELETE /api/v1/floors/bulk`
  - Body: `{ "ids": [1,2,3] }`
- `GET /api/v1/floors/export?block_id=:blockId`
- `POST /api/v1/floors/import` (`multipart/form-data`, `file`)
- `GET /api/v1/floors/import-template`

### Units
- `DELETE /api/v1/units/bulk`
  - Body: `{ "ids": [1,2,3] }`
- `GET /api/v1/units/export?floor_id=:floorId`
- `POST /api/v1/units/import` (`multipart/form-data`, `file`)
- `GET /api/v1/units/import-template`

## Import response (opsiyonel)

Frontend, response `data` icinde bu alanlari okuyabilir:

```json
{
  "inserted_count": 10,
  "updated_count": 3,
  "skipped_count": 2,
  "error_rows": [
    { "row": 7, "message": "invalid site code" }
  ]
}
```

## Permission kodlari

- `site.delete`, `site.export`, `site.import`
- `block.delete`, `block.export`, `block.import`
- `floor.delete`, `floor.export`, `floor.import`
- `unit.delete`, `unit.export`, `unit.import`

## Frontend fallback davranisi

- Endpoint `404` veya `405` donerse:
  - Bulk delete: `"Bulk delete endpoint is not available yet."`
  - Export: `"Export endpoint is not available yet."`
  - Import: `"Import endpoint is not available yet."`
  - Template: `"Template endpoint is not available yet."`

## Backend mevcutluk notu

Bu task kapsaminda `backend/app/Config/Routes.php` icinde `bulk`, `export`, `import`, `import-template` route tanimi bulunmamistir. UI kontrati hazirdir; backend endpointleri eklendiginde frontend dogrudan calisacaktir.
