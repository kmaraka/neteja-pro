import { Route, Routes } from 'react-router'
import Contained from './components/Contained'
import CookieBanner from './components/CookieBanner'
import ShopLayout from './components/ShopLayout'
import { GuestOnly, RequireAuth } from './features/auth/guards'
import { CartProvider } from './features/cart/CartProvider'
import Account from './pages/Account'
import CartPage from './pages/CartPage'
import ChangePassword from './pages/ChangePassword'
import Consents from './pages/Consents'
import EmailVerified from './pages/EmailVerified'
import ForgotPassword from './pages/ForgotPassword'
import HomePage from './pages/HomePage'
import { Cgu, Privacy } from './pages/Legal'
import Login from './pages/Login'
import NotFound from './pages/NotFound'
import ProductPage from './pages/ProductPage'
import Register from './pages/Register'
import ResetPassword from './pages/ResetPassword'
import ShopPage from './pages/ShopPage'

export default function App() {
  return (
    <CartProvider>
      <Routes>
        {/* Boutique : ouverte à tous */}
        <Route element={<ShopLayout />}>
          <Route path="/" element={<HomePage />} />
          <Route element={<Contained />}>
            <Route path="/boutique" element={<ShopPage />} />
            <Route path="/produits/:slug" element={<ProductPage />} />
            <Route path="/panier" element={<CartPage />} />
          </Route>
        </Route>

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

        <Route path="*" element={<NotFound />} />
      </Routes>
      <CookieBanner />
    </CartProvider>
  )
}