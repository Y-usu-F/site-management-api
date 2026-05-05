import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'

import App from '@/app/App.tsx'
import { configureApiClient } from '@/shared/api/client'
import { useAuthStore } from '@/features/auth/auth.store'

import './index.css'

function trimTrailingSlash(url: string): string {
  return url.replace(/\/$/, '')
}

configureApiClient({
  getBaseUrl: () => trimTrailingSlash(import.meta.env.VITE_API_BASE_URL ?? ''),
  getAccessToken: () => useAuthStore.getState().accessToken,
  getRefreshToken: () => useAuthStore.getState().refreshToken,
  persistTokens: (data) => useAuthStore.getState().applyTokenRotation(data),
  clearSession: () => useAuthStore.getState().clearSession(),
  onSessionExpired: () => {
    window.location.assign('/login')
  },
})

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
