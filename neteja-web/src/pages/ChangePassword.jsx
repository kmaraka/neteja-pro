import { useState } from 'react'
import { Link, useNavigate } from 'react-router'
import AuthLayout from '../components/AuthLayout'
import { Alert, Button, Field } from '../components/ui'
import { useAuth } from '../features/auth/useAuth'
import { api } from '../lib/api'
import { fieldError, parseError } from '../lib/errors'

export default function ChangePassword() {
  const { user, refresh, logout } = useAuth()
  const navigate = useNavigate()
  const forced = user?.must_change_password

  const [form, setForm] = useState({ current_password: '', password: '', password_confirmation: '' })
  const [error, setError] = useState(null)
  const [busy, setBusy] = useState(false)

  const update = (event) =>
    setForm((current) => ({ ...current, [event.target.name]: event.target.value }))

  async function submit(event) {
    event.preventDefault()
    setBusy(true)
    setError(null)
    try {
      await api.post('/api/auth/password/change', form)
      await refresh() // must_change_password passe à false
      navigate('/compte', { replace: true })
    } catch (err) {
      setError(parseError(err))
      setBusy(false)
    }
  }

  const err = (name) => fieldError(error?.fields, name)

  return (
    <AuthLayout
      title="Changer mon mot de passe"
      subtitle={
        forced
          ? 'Pour votre sécurité, remplacez le mot de passe temporaire reçu par SMS.'
          : undefined
      }
      footer={
        forced ? (
          <button onClick={logout} className="text-primary underline">
            Se déconnecter
          </button>
        ) : (
          <Link to="/compte" className="text-primary underline">
            Retour à mon compte
          </Link>
        )
      }
    >
      <form onSubmit={submit} className="space-y-4" noValidate>
        {error && !error.hasFields && <Alert variant="error">{error.message}</Alert>}
        <Field
          label={forced ? 'Mot de passe temporaire' : 'Mot de passe actuel'}
          name="current_password"
          type="password"
          autoComplete="current-password"
          value={form.current_password}
          onChange={update}
          error={err('current_password')}
        />
        <Field
          label="Nouveau mot de passe"
          name="password"
          type="password"
          autoComplete="new-password"
          hint="8 caractères minimum, avec majuscules, minuscules et chiffres."
          value={form.password}
          onChange={update}
          error={err('password')}
        />
        <Field
          label="Confirmer le nouveau mot de passe"
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          value={form.password_confirmation}
          onChange={update}
        />
        <Button type="submit" loading={busy}>
          Changer le mot de passe
        </Button>
      </form>
    </AuthLayout>
  )
}