import { Navigate, Route, Routes } from 'react-router'
import CookieBanner from './components/CookieBanner'
import { GuestOnly, RequireAuth } from './features/auth/guards'
import Account from './pages/Account'
import ChangePassword from './pages/ChangePassword'
import Consents from './pages/Consents'
import EmailVerified from './pages/EmailVerified'
import ForgotPassword from './pages/ForgotPassword'
import { Cgu, Privacy } from './pages/Legal'
import Login from './pages/Login'
import NotFound from './pages/NotFound'
import Register from './pages/Register'
import ResetPassword from './pages/ResetPassword'

export default function App() {
  return (
    <>
      <Routes>
        {/* Visiteurs uniquement */}
        <Route element={<GuestOnly />}>
          <Route path="/connexion" element={<Login />} />
          <Route path="/inscription" element={<Register />} />
          <Route path="/mot-de-passe-oublie" element={<ForgotPassword />} />
        </Route>

        {/* Ouvertes à tous */}
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route path="/email-verifie" element={<EmailVerified />} />
        <Route path="/cgu" element={<Cgu />} />
        <Route path="/confidentialite" element={<Privacy />} />

        {/* Utilisateurs connectés */}
        <Route element={<RequireAuth />}>
          <Route path="/compte" element={<Account />} />
          <Route path="/changer-mot-de-passe" element={<ChangePassword />} />
          <Route path="/conditions" element={<Consents />} />
        </Route>

        <Route path="/" element={<Navigate to="/compte" replace />} />
        <Route path="*" element={<NotFound />} />
      </Routes>
      <CookieBanner />
    </>
  )
}