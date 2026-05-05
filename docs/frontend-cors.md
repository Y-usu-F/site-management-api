# Frontend CORS (TODO)

The React dashboard (`frontend/`, Vite dev server) calls the CodeIgniter API from the browser. Unless CORS headers allow the dev origin, the browser will block responses before the SPA can read them.

## Required allowed origin (local development)

- `http://localhost:5173`

(Vite’s default port; adjust if you run `npm run dev -- --port …`.)

## Backend configuration (implemented)

The API reads CORS from environment (see `backend/.env.docker.example`):

| Variable | Purpose |
|----------|---------|
| `CORS_ALLOWED_ORIGINS` | Comma-separated exact origins (no `*` in **production**). |
| `CORS_ALLOWED_METHODS` | e.g. `GET,POST,PUT,PATCH,DELETE,OPTIONS` |
| `CORS_ALLOWED_HEADERS` | Must include `Authorization`, `Content-Type`, `Idempotency-Key`, `X-Request-Id` as needed. |
| `CORS_ALLOW_CREDENTIALS` | `true`/`false` — never combine `*` origins with credentials. |

`OPTIONS` requests under `api/v1/*` are answered so preflight succeeds before verb-specific routes run.

Do **not** weaken API auth or move secrets to the frontend to work around CORS—the fix belongs on the server.

## Related

- API base URL for the SPA: `VITE_API_BASE_URL` (see `frontend/.env.example`).
