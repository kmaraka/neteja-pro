import { useState } from 'react'
import { Link, useParams } from 'react-router'
import { Alert, Button } from '../components/ui'
import { useCart } from '../features/cart/useCart'
import { firstMessage, parseError } from '../lib/errors'
import { fcfa } from '../lib/format'
import { useFetch } from '../lib/useFetch'

export default function ProductPage() {
  const { slug } = useParams()
  const { data, error, loading } = useFetch(`/api/products/${slug}`)
  const { addItem, busy } = useCart()

  const [selectedId, setSelectedId] = useState(null)
  const [quantity, setQuantity] = useState(1)
  const [status, setStatus] = useState({ kind: 'idle' })

  if (loading) return <p className="text-secondary">Chargement…</p>

  if (error || !data) {
    return (
      <div className="space-y-3">
        <p>Ce produit n&apos;est plus disponible.</p>
        <Link to="/boutique" className="font-medium text-primary underline">
          Retour à la boutique
        </Link>
      </div>
    )
  }

  const product = data.data
  // Par défaut : la première variante en stock.
  const current =
    product.variants.find((variant) => variant.id === selectedId) ??
    product.variants.find((variant) => variant.in_stock) ??
    product.variants[0]

  async function add(event) {
    event.preventDefault()
    if (busy) return
    setStatus({ kind: 'idle' })

    try {
      await addItem(current.id, quantity)
      setStatus({ kind: 'added' })
    } catch (err) {
      setStatus({ kind: 'error', message: firstMessage(parseError(err)) })
    }
  }

  return (
    <div className="grid gap-8 md:grid-cols-2">
      <div className="aspect-square overflow-hidden rounded-lg bg-accent/25">
        {product.image_url ? (
          <img src={product.image_url} alt={product.name} className="size-full object-cover" />
        ) : (
          <div
            aria-hidden="true"
            className="flex size-full items-center justify-center text-7xl font-semibold text-primary/60"
          >
            {product.name.charAt(0)}
          </div>
        )}
      </div>

      <div>
        <nav aria-label="Fil d'Ariane" className="text-sm text-secondary">
          <Link to="/boutique" className="hover:underline">
            Boutique
          </Link>{' '}
          /{' '}
          <Link to={`/boutique?category=${encodeURIComponent(product.category.slug)}`} className="hover:underline">
            {product.category.name}
          </Link>
        </nav>

        <h1 className="mt-2 text-3xl font-semibold tracking-tight">{product.name}</h1>
        <p className="mt-2 text-2xl font-semibold tabular-nums text-primary">{fcfa(current.price_fcfa)}</p>

        {product.description && (
          <p className="mt-4 max-w-prose whitespace-pre-line text-secondary">{product.description}</p>
        )}

        <form onSubmit={add} className="mt-6 space-y-4">
          {product.variants.length > 1 && (
            <fieldset>
              <legend className="text-sm font-medium">Choisissez une option</legend>
              <div className="mt-2 flex flex-wrap gap-2">
                {product.variants.map((variant) => (
                  <label
                    key={variant.id}
                    className={`cursor-pointer rounded-lg border px-3 py-2 text-sm focus-within:outline-2 focus-within:outline-primary ${
                      variant.id === current.id ? 'border-primary bg-primary/5 font-medium' : 'border-ink/20'
                    } ${variant.in_stock ? '' : 'opacity-50'}`}
                  >
                    <input
                      type="radio"
                      name="variant"
                      value={variant.id}
                      checked={variant.id === current.id}
                      onChange={() => {
                        setSelectedId(variant.id)
                        setStatus({ kind: 'idle' })
                      }}
                      className="sr-only"
                    />
                    {variant.name}
                    {!variant.in_stock && ' (rupture)'}
                  </label>
                ))}
              </div>
            </fieldset>
          )}

          {current.stock_left && <p className="text-sm font-medium text-red-700">Plus que {current.stock_left} en stock.</p>}

          <div className="flex items-end gap-3">
            <div>
              <label htmlFor="quantity" className="block text-sm font-medium">
                Quantité
              </label>
              <select
                id="quantity"
                value={quantity}
                onChange={(event) => setQuantity(Number(event.target.value))}
                className="mt-1 rounded-lg border border-gray-300 px-3 py-2"
              >
                {Array.from({ length: 10 }, (_, index) => index + 1).map((value) => (
                  <option key={value} value={value}>
                    {value}
                  </option>
                ))}
              </select>
            </div>

            <div className="flex-1">
              <Button type="submit" loading={busy} disabled={!current.in_stock}>
                {current.in_stock ? 'Ajouter au panier' : 'Rupture de stock'}
              </Button>
            </div>
          </div>

          {status.kind === 'added' && (
            <Alert variant="success">
              Ajouté au panier.{' '}
              <Link to="/panier" className="font-medium underline">
                Voir le panier
              </Link>
            </Alert>
          )}
          {status.kind === 'error' && <Alert variant="error">{status.message}</Alert>}
        </form>
      </div>
    </div>
  )
}