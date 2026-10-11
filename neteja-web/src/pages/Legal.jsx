import { Link } from 'react-router'
import AuthLayout from '../components/AuthLayout'

export function Cgu() {
  return (
    <AuthLayout title="Conditions générales d'utilisation" footer={<Link to="/connexion" className="text-primary underline">Retour</Link>}>
      <p className="text-sm text-gray-600">
        Texte à fournir par Neteja (version {'1.0'}). Cette page affichera les conditions
        générales d&apos;utilisation acceptées à l&apos;inscription.
      </p>
    </AuthLayout>
  )
}

export function Privacy() {
  return (
    <AuthLayout title="Politique de confidentialité" footer={<Link to="/connexion" className="text-primary underline">Retour</Link>}>
      <p className="text-sm text-gray-600">
        Texte à fournir par Neteja (version {'1.0'}). Cette page décrira les données collectées,
        leur finalité, leur durée de conservation et les droits d&apos;accès, de rectification et
        d&apos;effacement (loi n°2013-450, APDP).
      </p>
    </AuthLayout>
  )
}