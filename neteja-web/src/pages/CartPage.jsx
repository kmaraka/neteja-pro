import { useState } from 'react'
import { Link } from 'react-router'
import { Alert, Button } from '../components/ui'
import { useAuth } from '../features/auth/useAuth'
import { useCart } from '../features/cart/useCart'
import { firstMessage, parseError } from '../lib/errors'
import { fcfa } from '../lib/format'
import { useFetch } from '../lib/useFetch'

const ISSUES = {
  unavailable: "Cet article n'est plus disponible. Retirez-le pour continuer.",
  insufficient_stock: 'Stock insuffisant pour cette quantité.',
}

export default function CartPage() {
  const { user } = useAuth()
  const { cart, commune, setCommune, loading, busy, updateItem, removeItem, applyPromo, removePromo } = useCart()
  const zones = useFetch('/api/delivery-zones')
  const [code, setCode] = useState('')
  const [message, setMessage] = useState(null)

  /** Exécute une action du panier et affiche le message d'erreur du serveur s'il y en a un. */
  async function run(action) {
    setMessage(null)
    try {
      await action()
    } catch (err) {
      setMessage(firstMessage(parseError(err)))
    }
  }

  async function submitPromo(event) {
    event.preventDefault()
    await run(() => applyPromo(code))
    setCode('')
  }

  if (loading) return <p className="text-secondary">Chargement…</p>

  if (cart.lines.length === 0) {
    return (
      <div className="space-y-3">
        <h1 className="text-2xl font-semibold tracking-tight">Mon panier</h1>
        <p>Votre panier est vide.</p>
        <Link to="/boutique" className="font-medium text-primary underline">
          Voir la boutique
        </Link>
      </div>
    )
  }

  return (
    <div>
      <h1 className="text-2xl font-semibold tracking-tight">Mon panier</h1>

      <div className="mt-6 grid gap-10 lg:grid-cols-[1fr_22rem]">
        <div>
          {message && <Alert variant="error">{message}</Alert>}

          <ul className="divide-y divide-ink/10">
            {cart.lines.map((line) => (
              <li key={line.item_id} className="flex gap-4 py-4">
                <div className="size-20 shrink-0 overflow-hidden rounded-lg bg-accent/25">
                  {line.image_url ? (
                    <img src={line.image_url} alt="" loading="lazy" className="size-full object-cover" />
                  ) : (
                    <div
                      aria-hidden="true"
                      className="flex size-full items-center justify-center text-2xl font-semibold text-primary/60"
                    >
                      {line.product_name.charAt(0)}
                    </div>
                  )}
                </div>

                <div className="flex-1">
                  <Link to={`/produits/${line.product_slug}`} className="font-medium hover:underline">
                    {line.product_name}
                  </Link>
                  <p className="text-sm text-secondary">
                    {line.variant_name} · {fcfa(line.unit_price_fcfa)}
                  </p>

                  {line.issue && (
                    <p className="mt-1 text-sm font-medium text-red-700">
                      {line.issue === 'insufficient_stock' && line.stock_left != null
                        ? `Il n'en reste que ${line.stock_left}.`
                        : ISSUES[line.issue]}
                    </p>
                  )}

                  <div className="mt-2 flex items-center gap-2">
                    <button
                      type="button"
                      aria-label={`Diminuer la quantité de ${line.product_name}`}
                      disabled={busy || line.quantity <= 1}
                      onClick={() => run(() => updateItem(line.item_id, line.quantity - 1))}
                      className="size-8 rounded-full border border-ink/20 disabled:opacity-40"
                    >
                      −
                    </button>
                    <span className="w-6 text-center tabular-nums">{line.quantity}</span>
                    <button
                      type="button"
                      aria-label={`Augmenter la quantité de ${line.product_name}`}
                      disabled={busy}
                      onClick={() => run(() => updateItem(line.item_id, line.quantity + 1))}
                      className="size-8 rounded-full border border-ink/20 disabled:opacity-40"
                    >
                      +
                    </button>
                    <button
                      type="button"
                      disabled={busy}
                      onClick={() => run(() => removeItem(line.item_id))}
                      className="ml-3 text-sm text-secondary underline"
                    >
                      Retirer
                    </button>
                  </div>
                </div>

                <p className="font-semibold tabular-nums">{fcfa(line.line_total_fcfa)}</p>
              </li>
            ))}
          </ul>
        </div>

        <aside className="space-y-5 self-start">
          <div>
            <label htmlFor="commune" className="block text-sm font-medium">
              Commune de livraison
            </label>
            <select
              id="commune"
              value={commune}
              onChange={(event) => setCommune(event.target.value)}
              className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"
            >
              <option value="">Choisir une commune</option>
              {(zones.data?.data ?? []).map((zone) => (
                <option key={zone.commune} value={zone.commune}>
                  {zone.commune} ({fcfa(zone.fee_fcfa)})
                </option>
              ))}
            </select>
            {cart.delivery?.estimated_delay && (
              <p className="mt-1 text-sm text-secondary">Délai estimé : {cart.delivery.estimated_delay}</p>
            )}
          </div>

          {cart.promo ? (
            <p className="text-sm">
              Code {cart.promo.code} appliqué.{' '}
              <button
                type="button"
                disabled={busy}
                onClick={() => run(removePromo)}
                className="text-secondary underline"
              >
                Retirer le code
              </button>
            </p>
          ) : (
            <form onSubmit={submitPromo} className="space-y-1">
              <label htmlFor="promo" className="block text-sm font-medium">
                Code promo
              </label>
              <div className="flex gap-2">
                <input
                  id="promo"
                  value={code}
                  onChange={(event) => setCode(event.target.value)}
                  className="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2"
                />
                <button
                  type="submit"
                  disabled={busy || !code.trim()}
                  className="rounded-lg border border-primary px-4 py-2 font-medium text-primary hover:bg-primary/5 disabled:opacity-50"
                >
                  Appliquer
                </button>
              </div>
            </form>
          )}
          {cart.promo_error && <Alert variant="error">{cart.promo_error}</Alert>}

          <dl className="space-y-2 border-t border-ink/10 pt-4 text-sm">
            <div className="flex justify-between">
              <dt>Sous-total</dt>
              <dd className="tabular-nums">{fcfa(cart.subtotal_fcfa)}</dd>
            </div>
            {cart.discount_fcfa > 0 && (
              <div className="flex justify-between">
                <dt>Remise</dt>
                <dd className="tabular-nums">− {fcfa(cart.discount_fcfa)}</dd>
              </div>
            )}
            <div className="flex justify-between">
              <dt>Livraison</dt>
              <dd className="tabular-nums">
                {cart.delivery_fee_fcfa != null ? fcfa(cart.delivery_fee_fcfa) : 'À choisir'}
              </dd>
            </div>
            <div className="flex justify-between border-t border-ink/10 pt-2 text-base font-semibold">
              <dt>Total</dt>
              <dd className="tabular-nums">{fcfa(cart.total_fcfa)}</dd>
            </div>
          </dl>
          {cart.delivery_error && <Alert variant="error">{cart.delivery_error}</Alert>}

          {user ? (
            <Button type="button" disabled>
              Commander
            </Button>
          ) : (
            <Link
              to="/connexion"
              state={{ from: { pathname: '/panier' } }}
              className="block rounded-lg bg-primary px-4 py-2.5 text-center font-semibold text-white hover:bg-primary/90"
            >
              Se connecter pour commander
            </Link>
          )}
          <p className="text-sm text-secondary">Le passage de commande arrive à la prochaine étape.</p>
        </aside>
      </div>
    </div>
  )
}