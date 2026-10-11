import { Navigate, Outlet, useLocation } from 'react-router'
import { useAuth } from './useAuth'

function Splash() {
  return (
    <div className="flex min-h-screen items-center justify-center text-gray-500">Chargement…</div>
  )
}

// Pages accessibles même si des consentements sont en attente.
const OPEN_WITH_PENDING_CONSENTS = ['/conditions', '/changer-mot-de-passe']

/** Réservé aux utilisateurs connectés, dans le bon état. */
export function RequireAuth() {
  const { user, pending, loading } = useAuth()
  const location = useLocation()

  if (loading) return <Splash />

  if (!user) {
    return <Navigate to="/connexion" replace state={{ from: location }} />
  }

  if (user.must_change_password && location.pathname !== '/changer-mot-de-passe') {
    return <Navigate to="/changer-mot-de-passe" replace />
  }

  if (
    !user.must_change_password &&
    pending.length > 0 &&
    !OPEN_WITH_PENDING_CONSENTS.includes(location.pathname)
  ) {
    return <Navigate to="/conditions" replace />
  }

  return <Outlet />
}

/** Pages réservées aux visiteurs (connexion, inscription) : un connecté est redirigé. */
export function GuestOnly() {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) return <Splash />

  if (user) {
    return <Navigate to={location.state?.from?.pathname ?? '/compte'} replace />
  }

  return <Outlet />
}