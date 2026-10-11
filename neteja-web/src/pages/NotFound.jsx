import { Link } from 'react-router'
import AuthLayout from '../components/AuthLayout'

export default function NotFound() {
  return (
    <AuthLayout title="Page introuvable">
      <p className="text-sm text-gray-600">Cette page n&apos;existe pas.</p>
      <Link to="/" className="mt-4 inline-block text-sm font-medium text-primary underline">
        Retour à l&apos;accueil
      </Link>
    </AuthLayout>
  )
}