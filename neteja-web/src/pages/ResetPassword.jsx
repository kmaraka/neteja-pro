import { useState } from 'react'
import { Link, useSearchParams } from 'react-router'
import AuthLayout from '../components/AuthLayout'
import { Alert, Button, Field } from '../components/ui'
import { api, csrf } from '../lib/api'
import { fieldError, parseError } from '../lib/errors'

export default function ResetPassword() {
  const [params] = useSearchParams()
  const token = params.get('token')
  const email = params.get('email')

  const [form, setForm] = useState({ password: '', password_confirmation: '' })
  const [error, setError] = useState(null)
  const [done, setDone] = useState(false)
  const [busy, setBusy] = useState(false)

  const update = (event) =>
    setForm((current) => ({ ...current, [event.target.name]: event.target.value }))

  async function submit(event) {
    event.preventDefault()
    setBusy(true)
    setError(null)
    try {
      await csrf()
      await api.post('/api/auth/password/reset', { token, email, ...form })
      setDone(true)
    } catch (err) {
      setError(parseError(err))
    } finally {
      setBusy(false)
    }
  }

  if (!token || !email) {
    return (
      <AuthLayout title="Lien invalide">
        <Alert variant="error">Ce lien est incomplet. Demandez un nouveau lien de réinitialisation.</Alert>
        <p className="mt-4 text-sm">
          <Link to="/mot-de-passe-oublie" className="text-primary underline">
            Demander un nouveau lien
          </Link>
        </p>
      </AuthLayout>
    )
  }

  if (done) {
    return (
      <AuthLayout title="Mot de passe modifié">
        <Alert variant="success">Votre mot de passe a été réinitialisé.</Alert>
        <p className="mt-4 text-sm">
          <Link to="/connexion" className="font-medium text-primary underline">
            Se connecter
          </Link>
        </p>
      </AuthLayout>
    )
  }

  const linkError = fieldError(error?.fields, 'email') // "Lien invalide ou expiré."

  return (
    <AuthLayout title="Nouveau mot de passe" subtitle={`Compte : ${email}`}>
      <form onSubmit={submit} className="space-y-4" noValidate>
        {linkError && <Alert variant="error">{linkError}</Alert>}
        {error && !error.hasFields && <Alert variant="error">{error.message}</Alert>}
        <Field
          label="Nouveau mot de passe"
          name="password"
          type="password"
          autoComplete="new-password"
          hint="8 caractères minimum, avec majuscules, minuscules et chiffres."
          value={form.password}
          onChange={update}
          error={fieldError(error?.fields, 'password')}
        />
        <Field
          label="Confirmer le mot de passe"
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          value={form.password_confirmation}
          onChange={update}
        />
        <Button type="submit" loading={busy}>
          Enregistrer
        </Button>
      </form>
    </AuthLayout>
  )
}