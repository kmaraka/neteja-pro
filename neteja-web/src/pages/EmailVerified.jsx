import { Link } from 'react-router'
import AuthLayout from '../components/AuthLayout'
import { Alert } from '../components/ui'

export default function EmailVerified() {
  return (
    <AuthLayout title="Email vérifié">
      <Alert variant="success">Merci ! Votre adresse email est confirmée. Vous pouvez vous connecter.</Alert>
      <p className="mt-4 text-sm">
        <Link to="/connexion" className="font-medium text-primary underline">
          Se connecter
        </Link>
      </p>
    </AuthLayout>
  )
}