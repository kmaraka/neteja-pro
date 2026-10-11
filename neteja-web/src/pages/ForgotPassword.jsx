import { useState } from 'react'
import { Link } from 'react-router'
import AuthLayout from '../components/AuthLayout'
import { Alert, Button, Field } from '../components/ui'
import { api, csrf } from '../lib/api'
import { fieldError, parseError } from '../lib/errors'

export default function ForgotPassword() {
  const [email, setEmail] = useState('')
  const [error, setError] = useState(null)
  const [message, setMessage] = useState(null)
  const [busy, setBusy] = useState(false)

  async function submit(event) {
    event.preventDefault()
    setBusy(true)
    setError(null)
    try {
      await csrf()
      const { data } = await api.post('/api/auth/password/forgot', { email })
      setMessage(data.message) // même message, que le compte existe ou non
    } catch (err) {
      setError(parseError(err))
    } finally {
      setBusy(false)
    }
  }

  return (
    <AuthLayout
      title="Mot de passe oublié"
      subtitle="Saisissez votre email : nous vous enverrons un lien de réinitialisation."
      footer={
        <Link to="/connexion" className="font-medium text-primary underline">
          Retour à la connexion
        </Link>
      }
    >
      {message ? (
        <Alert variant="success">{message}</Alert>
      ) : (
        <form onSubmit={submit} className="space-y-4" noValidate>
          {error && !error.hasFields && <Alert variant="error">{error.message}</Alert>}
          <Field
            label="Email"
            name="email"
            type="email"
            autoComplete="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            error={fieldError(error?.fields, 'email')}
          />
          <Button type="submit" loading={busy}>
            Envoyer le lien
          </Button>
        </form>
      )}
    </AuthLayout>
  )
}