import { BrowserRouter } from 'react-router-dom'

import { AppRoutes } from '@/app/router'
import { AppProviders } from '@/app/providers'

export default function App() {
  return (
    <AppProviders>
      <BrowserRouter>
        <AppRoutes />
      </BrowserRouter>
    </AppProviders>
  )
}
