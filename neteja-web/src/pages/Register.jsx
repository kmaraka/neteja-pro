import { useState } from 'react'
import { Link } from 'react-router'
import AuthLayout from '../components/AuthLayout'
import { Alert, Button, Checkbox, Field } from '../components/ui'
import { useAuth } from '../features/auth/useAuth'
import { api, csrf } from '../lib/api'
import { fieldError, parseError } from '../lib/errors'

const initial = {
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  accept_terms: false,
}

export default function Register() {
  const { register } = useAuth()
  const [form, setForm] = useState(initial)
  const [error, setError] = useState(null)
  const [busy, setBusy] = useState(false)
  const [done, setDone] = useState(false)
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
      await register({ ...form, phone: form.phone || null })
      setDone(true)
    } catch (err) {
      setError(parseError(err))
    } finally {
      setBusy(false)
    }
  }

  async function resend() {
    try {
      await csrf()
      await api.post('/api/auth/email/resend', { email: form.email })
      setResent(true)
    } catch (err) {
      setError(parseError(err))
    }
  }

  if (done) {
    return (
      <AuthLayout title="Vérifiez votre email">
        <div className="space-y-4">
          <Alert variant="success">
            Compte créé. Nous avons envoyé un lien de vérification à <strong>{form.email}</strong>.
            Cliquez dessus pour activer votre compte.
          </Alert>
          {resent ? (
            <p className="text-sm text-green-700">Un nouvel email vient d&apos;être envoyé.</p>
          ) : (
            <button onClick={resend} className="text-sm text-primary underline">
              Je n&apos;ai rien reçu : renvoyer l&apos;email
            </button>
          )}
          <p className="text-sm">
            <Link to="/connexion" className="font-medium text-primary underline">
              Aller à la connexion
            </Link>
          </p>
        </div>
      </AuthLayout>
    )
  }

  const err = (name) => fieldError(error?.fields, name)

  return (
    <AuthLayout
      title="Créer un compte"
      footer={
        <>
          Déjà inscrit ?{' '}
          <Link to="/connexion" className="font-medium text-primary underline">
            Se connecter
          </Link>
        </>
      }
    >
      <form onSubmit={submit} className="space-y-4" noValidate>
        {error && !error.hasFields && <Alert variant="error">{error.message}</Alert>}

        <Field label="Nom complet" name="name" autoComplete="name" value={form.name} onChange={update} error={err('name')} />
        <Field label="Email" name="email" type="email" autoComplete="email" value={form.email} onChange={update} error={err('email')} />
        <Field
          label="Téléphone (facultatif)"
          name="phone"
          type="tel"
          autoComplete="tel"
          hint="Exemple : 07 00 00 00 00"
          value={form.phone}
          onChange={update}
          error={err('phone')}
        />
        <Field
          label="Mot de passe"
          name="password"
          type="password"
          autoComplete="new-password"
          hint="8 caractères minimum, avec majuscules, minuscules et chiffres."
          value={form.password}
          onChange={update}
          error={err('password')}
        />
        <Field
          label="Confirmer le mot de passe"
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          value={form.password_confirmation}
          onChange={update}
        />
        <Checkbox
          name="accept_terms"
          checked={form.accept_terms}
          onChange={update}
          error={err('accept_terms')}
          label={
            <>
              J&apos;accepte les{' '}
              <Link to="/cgu" target="_blank" className="text-primary underline">
                conditions d&apos;utilisation
              </Link>{' '}
              et la{' '}
              <Link to="/confidentialite" target="_blank" className="text-primary underline">
                politique de confidentialité
              </Link>
              .
            </>
          }
        />

        <Button type="submit" loading={busy}>
          Créer mon compte
        </Button>
      </form>
    </AuthLayout>
  )
}