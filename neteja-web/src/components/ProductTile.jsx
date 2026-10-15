import { Link } from 'react-router'
import { fcfa } from '../lib/format'

export default function ProductTile({ product, className = '' }) {
  return (
    <li className={className}>
      <Link to={`/produits/${product.slug}`} className="group block">
        <div className="aspect-square overflow-hidden rounded-lg bg-accent/25">
          {product.image_url ? (
            <img src={product.image_url} alt="" loading="lazy" className="size-full object-cover" />
          ) : (
            <div
              aria-hidden="true"
              className="flex size-full items-center justify-center font-display text-5xl font-semibold text-primary/60"
            >
              {product.name.charAt(0)}
            </div>
          )}
        </div>
        <h2 className="mt-2 font-medium group-hover:underline">{product.name}</h2>
      </Link>
      <p className="text-sm tabular-nums text-secondary">À partir de {fcfa(product.price_from_fcfa)}</p>
      {!product.in_stock && <p className="text-sm font-medium text-red-700">Rupture de stock</p>}
    </li>
  )
}