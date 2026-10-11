import { useState } from 'react'
import { Link, useNavigate } from 'react-router'
import AuthLayout from '../components/AuthLayout'
import { Alert, Button, Checkbox } from '../components/ui'
import { useAuth } from '../features/auth/useAuth'
import { parseError } from '../lib/errors'

const DOCUMENTS = {
  cgu: { label: "les conditions générales d'utilisation", to: '/cgu' },
  politique_confidentialite: { label: 'la politique de confidentialité', to: '/confidentialite' },
}

export default function Consents() {
  const { pending, acceptConsents, logout, deleteAccount } = useAuth()
  const navigate = useNavigate()
  const [accepted, setAccepted] = useState(false)
  const [confirming, setConfirming] = useState(false)
  const [error, setError] = useState(null)
  const [busy, setBusy] = useState(false)

  async function submit(event) {
    event.preventDefault()
    if (!accepted) return
    setBusy(true)
    setError(null)
    try {
      await acceptConsents(pending)
      navigate('/compte', { replace: true })
    } catch (err) {
      setError(parseError(err))
      setBusy(false)
    }
  }

  // Droit à l'effacement : refuser les conditions permet de supprimer son compte.
  async function refuse() {
    setError(null)
    try {
      await deleteAccount()
    } catch (err) {
      setError(parseError(err))
    }
  }

  return (
    <AuthLayout
      title="Conditions d'utilisation"
      subtitle="Nos documents ont été mis à jour. Merci de les accepter pour continuer."
      footer={
        <div className="space-y-2">
          <button onClick={logout} className="text-primary underline">
            Se déconnecter
          </button>
          {confirming ? (
            <div className="space-y-1">
              <p className="text-red-700">
                Votre compte sera anonymisé définitivement. Cette action est irréversible.
              </p>
              <button onClick={refuse} className="font-medium text-red-700 underline">
                Oui, refuser et supprimer mon compte
              </button>{' '}
              <button onClick={() => setConfirming(false)} className="text-gray-600 underline">
                Annuler
              </button>
            </div>
          ) : (
            <div>
              <button onClick={() => setConfirming(true)} className="text-red-700 underline">
                Refuser et supprimer mon compte
              </button>
            </div>
          )}
        </div>
      }
    >
      <form onSubmit={submit} className="space-y-4">
        {error && <Alert variant="error">{error.message}</Alert>}
        <ul className="list-inside list-disc text-sm text-gray-700">
          {pending.map((type) => (
            <li key={type}>
              <Link to={DOCUMENTS[type]?.to ?? '#'} target="_blank" className="text-primary underline">
                {DOCUMENTS[type]?.label ?? type}
              </Link>
            </li>
          ))}
        </ul>
        <Checkbox
          name="accepted"
          checked={accepted}
          onChange={(event) => setAccepted(event.target.checked)}
          label="J'ai lu et j'accepte ces documents."
        />
        <Button type="submit" loading={busy} disabled={!accepted}>
          Continuer
        </Button>
      </form>
    </AuthLayout>
  )
}