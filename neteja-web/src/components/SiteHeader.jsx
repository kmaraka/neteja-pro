import { useState } from 'react'
import { Link, useNavigate } from 'react-router'
import { useAuth } from '../features/auth/useAuth'
import { useCart } from '../features/cart/useCart'
import { Cart, Close, Menu, Search, User } from './icons'

function SearchForm({ onSearch, className = '' }) {
  const navigate = useNavigate()

  function submit(event) {
    event.preventDefault()
    const term = String(new FormData(event.currentTarget).get('search')).trim()

    if (term.length >= 2) {
      navigate({ pathname: '/boutique', search: `?search=${encodeURIComponent(term)}` })
      onSearch?.()
    }
  }

  return (
    <form onSubmit={submit} role="search" className={`relative ${className}`}>
      <label htmlFor="header-search" className="sr-only">
        Rechercher un produit
      </label>
      <input
        id="header-search"
        name="search"
        type="search"
        minLength={2}
        placeholder="Rechercher un produit"
        className="w-full rounded-full border border-ink/15 bg-paper py-2 pl-4 pr-10 text-sm"
      />
      <button type="submit" aria-label="Lancer la recherche" className="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-full p-1.5 text-primary">
        <Search className="size-4" />
      </button>
    </form>
  )
}

export default function SiteHeader({ categories }) {
  const { user } = useAuth()
  const { cart } = useCart()
  const [menuOpen, setMenuOpen] = useState(false)

  const links = [
    { to: '/boutique', label: 'Boutique' },
    ...categories.slice(0, 4).map((category) => ({
      to: `/boutique?category=${encodeURIComponent(category.slug)}`,
      label: category.name,
    })),
  ]

  const accountLink = user ? { to: '/compte', label: 'Mon compte' } : { to: '/connexion', label: 'Se connecter' }

  return (
    <header className="border-b border-ink/10 bg-white">
      <div className="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 md:px-8">
        <button
          type="button"
          aria-label="Ouvrir le menu"
          onClick={() => setMenuOpen(true)}
          className="-ml-2 rounded-full p-2 lg:hidden"
        >
          <Menu />
        </button>

        <Link to="/" className="font-display text-2xl font-semibold tracking-tight text-primary">
          Neteja
        </Link>

        <nav aria-label="Navigation principale" className="ml-4 hidden items-center gap-6 text-sm font-medium lg:flex">
          {links.map((link) => (
            <Link key={link.to} to={link.to} className="hover:text-primary hover:underline underline-offset-4">
              {link.label}
            </Link>
          ))}
        </nav>

        <div className="flex-1" />

        <SearchForm className="hidden w-48 md:block lg:w-64" />

        <Link to={accountLink.to} className="flex items-center gap-2 rounded-full p-2 text-sm font-medium hover:bg-ink/5">
          <User />
          <span className="hidden xl:inline">{accountLink.label}</span>
          <span className="sr-only xl:hidden">{accountLink.label}</span>
        </Link>

        <Link to="/panier" aria-label={`Panier (${cart.items_count})`} className="relative rounded-full p-2 hover:bg-ink/5">
          <Cart />
          {cart.items_count > 0 && (
            <span className="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-citron px-1 text-xs font-semibold text-night">
              {cart.items_count}
            </span>
          )}
        </Link>
      </div>

      {menuOpen && (
        <div
          role="dialog"
          aria-modal="true"
          aria-label="Menu"
          className="fixed inset-0 z-50 lg:hidden"
          onKeyDown={(event) => event.key === 'Escape' && setMenuOpen(false)}
        >
          <button
            type="button"
            aria-label="Fermer le menu"
            onClick={() => setMenuOpen(false)}
            className="absolute inset-0 bg-night/60"
          />

          <div className="absolute inset-y-0 left-0 flex w-80 max-w-[85%] flex-col gap-6 overflow-y-auto bg-white p-5">
            <div className="flex items-center justify-between">
              <span className="font-display text-2xl font-semibold text-primary">Neteja</span>
              <button type="button" aria-label="Fermer le menu" onClick={() => setMenuOpen(false)} className="rounded-full p-2">
                <Close />
              </button>
            </div>

            <SearchForm onSearch={() => setMenuOpen(false)} />

            <nav aria-label="Menu mobile">
              <ul className="space-y-1 text-lg">
                {links.map((link) => (
                  <li key={link.to}>
                    <Link to={link.to} onClick={() => setMenuOpen(false)} className="block rounded-lg px-2 py-2 hover:bg-ink/5">
                      {link.label}
                    </Link>
                  </li>
                ))}
                <li>
                  <Link to={accountLink.to} onClick={() => setMenuOpen(false)} className="block rounded-lg px-2 py-2 hover:bg-ink/5">
                    {accountLink.label}
                  </Link>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      )}
    </header>
  )
}