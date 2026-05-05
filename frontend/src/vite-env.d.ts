/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
  /** When `"true"`, deny screens if JWT session has no permission codes (stricter prod posture). */
  readonly VITE_STRICT_PERMISSIONS?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
