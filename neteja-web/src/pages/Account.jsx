import { useState } from 'react'
import { Link } from 'react-router'
import AuthLayout from '../components/AuthLayout'
import { Alert, Button } from '../components/ui'
import { useAuth } from '../features/auth/useAuth'
import { api } from '../lib/api'
import { parseError } from '../lib/errors'

const PANEL_ROLES = ['super-admin', 'admin', 'superviseur']
const ADMIN_URL = import.meta.env.VITE_ADMIN_URL ?? 'http://localhost:8000/admin'

export default function Account() {
  const { user, logout, deleteAccount } = useAuth()
  const [message, setMessage] = useState(null)
  const [error, setError] = useState(null)
  const [confirming, setConfirming] = useState(false)

  const canUsePanel = user.roles.some((role) => PANEL_ROLES.includes(role))

  async function logoutOthers() {
    setError(null)
    try {
      await api.post('/api/auth/logout-all')
      setMessage('Tous les autres appareils ont été déconnectés.')
    } catch (err) {
      setError(parseError(err))
    }
  }

  async function erase() {
    setError(null)
    try {
      await deleteAccount() // le compte est anonymisé et la session fermée
    } catch (err) {
      setError(parseError(err))
    }
  }

  return (
    <AuthLayout title="Mon compte" subtitle={user.name}>
      <div className="space-y-4">
        {message && <Alert variant="success">{message}</Alert>}
        {error && <Alert variant="error">{error.message}</Alert>}

        <dl className="space-y-1 text-sm">
          <div className="flex justify-between">
            <dt className="text-gray-500">Email</dt>
            <dd>{user.email ?? '—'}</dd>
          </div>
          <div className="flex justify-between">
            <dt className="text-gray-500">Téléphone</dt>
            <dd>{user.phone ?? '—'}</dd>
          </div>
          <div className="flex justify-between">
            <dt className="text-gray-500">Rôles</dt>
            <dd>{user.roles.join(', ') || '—'}</dd>
          </div>
        </dl>

        <div className="space-y-2 border-t border-gray-100 pt-4 text-sm">
          <Link to="/changer-mot-de-passe" className="block text-primary underline">
            Changer mon mot de passe
          </Link>
          {canUsePanel && (
            <a href={ADMIN_URL} className="block text-primary underline">
              Ouvrir le back-office
            </a>
          )}
          <button onClick={logoutOthers} className="block text-primary underline">
            Déconnecter tous mes autres appareils
          </button>
        </div>

        <Button type="button" onClick={logout}>
          Se déconnecter
        </Button>

        <div className="border-t border-gray-100 pt-4 text-sm">
          {confirming ? (
            <div className="space-y-2">
              <Alert variant="error">
                Vos données personnelles seront effacées définitivement. Vos factures sont conservées
                (obligation légale). Cette action est irréversible.
              </Alert>
              <div className="flex gap-3">
                <button onClick={erase} className="font-medium text-red-700 underline">
                  Oui, supprimer mon compte
                </button>
                <button onClick={() => setConfirming(false)} className="text-gray-600 underline">
                  Annuler
                </button>
              </div>
            </div>
          ) : (
            <button onClick={() => setConfirming(true)} className="text-red-700 underline">
              Supprimer mon compte
            </button>
          )}
        </div>
      </div>
    </AuthLayout>
  )
}