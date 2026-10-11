import { useState } from 'react'
import { Link } from 'react-router'
import AuthLayout from '../components/AuthLayout'
import { Alert, Button, Checkbox, Field } from '../components/ui'
import { useAuth } from '../features/auth/useAuth'
import { api, csrf } from '../lib/api'
import { fieldError, parseError } from '../lib/errors'

export default function Login() {
  const { login } = useAuth()
  const [form, setForm] = useState({ login: '', password: '', remember: false })
  const [error, setError] = useState(null)
  const [busy, setBusy] = useState(false)
  const [resent, setResent] = useState(false)

  const update = (event) => {
    const { name, type, checked, value } = event.target
    setForm((current) => ({ ...current, [name]: type === 'checkbox' ? checked : value }))
  }

  async function submit(event) {
    event.preventDefault()
    setBusy(true)
    setError(null)
    try {
      await login(form) // succès : GuestOnly redirige tout seul
    } catch (err) {
      setError(parseError(err))
      setBusy(false)
    }
  }

  async function resend() {
    try {
      await csrf()
      await api.post('/api/auth/email/resend', { email: form.login })
      setResent(true)
    } catch (err) {
      setError(parseError(err))
    }
  }

  const unverified = error?.code === 'email_not_verified'

  return (
    <AuthLayout
      title="Connexion"
      footer={
        <>
          Pas encore de compte ?{' '}
          <Link to="/inscription" className="font-medium text-primary underline">
            Créer un compte
          </Link>
        </>
      }
    >
      <form onSubmit={submit} className="space-y-4" noValidate>
        {error && !error.hasFields && <Alert variant="error">{error.message}</Alert>}
        {unverified && form.login.includes('@') && (
          <div className="text-sm">
            {resent ? (
              <Alert variant="success">Un nouvel email de vérification vient d&apos;être envoyé.</Alert>
            ) : (
              <button type="button" onClick={resend} className="text-primary underline">
                Renvoyer l&apos;email de vérification
              </button>
            )}
          </div>
        )}

        <Field
          label="Email ou téléphone"
          name="login"
          autoComplete="username"
          value={form.login}
          onChange={update}
          error={fieldError(error?.fields, 'login')}
        />
        <Field
          label="Mot de passe"
          name="password"
          type="password"
          autoComplete="current-password"
          value={form.password}
          onChange={update}
          error={fieldError(error?.fields, 'password')}
        />

        <div className="flex items-center justify-between">
          <Checkbox label="Se souvenir de moi" name="remember" checked={form.remember} onChange={update} />
          <Link to="/mot-de-passe-oublie" className="text-sm text-primary underline">
            Mot de passe oublié ?
          </Link>
        </div>

        <Button type="submit" loading={busy}>
          Se connecter
        </Button>
      </form>
    </AuthLayout>
  )
}