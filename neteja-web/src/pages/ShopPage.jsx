import { Link, useOutletContext, useSearchParams } from 'react-router'
import ProductTile from '../components/ProductTile'
import { Alert } from '../components/ui'
import { useFetch } from '../lib/useFetch'

const SORTS = [
  { value: '', label: 'Nouveautés' },
  { value: 'price_asc', label: 'Prix croissant' },
  { value: 'price_desc', label: 'Prix décroissant' },
]

export default function ShopPage() {
  const [params, setParams] = useSearchParams()
  const category = params.get('category') ?? ''
  const search = params.get('search') ?? ''
  const sort = params.get('sort') ?? ''
  const page = Number(params.get('page') ?? 1)

  const { categories } = useOutletContext()
  const products = useFetch('/api/products', params.toString())

  /** Modifie un ou plusieurs filtres. Un nouveau filtre revient à la page 1. */
  function update(changes) {
    const next = new URLSearchParams(params)

    for (const [key, value] of Object.entries(changes)) {
      if (value) next.set(key, value)
      else next.delete(key)
    }

    if (!('page' in changes)) next.delete('page')
    setParams(next)
  }

  function submitSearch(event) {
    event.preventDefault()
    update({ search: String(new FormData(event.currentTarget).get('search')).trim() })
  }

  const list = products.data?.data ?? []
  const meta = products.data?.meta

  return (
    <div className="grid gap-8 md:grid-cols-[14rem_1fr]">
      <aside>
        <h1 className="text-2xl font-semibold tracking-tight">Boutique</h1>

        <nav aria-label="Catégories" className="mt-4">
          <ul className="space-y-1 text-sm">
            <li>
              <Link
                to="/boutique"
                aria-current={category === '' ? 'page' : undefined}
                className={category === '' ? 'font-semibold text-primary' : 'hover:underline'}
              >
                Tous les produits
              </Link>
            </li>
            {categories.map((item) => (
              <li key={item.slug}>
                <Link
                  to={`/boutique?category=${encodeURIComponent(item.slug)}`}
                  aria-current={category === item.slug ? 'page' : undefined}
                  className={category === item.slug ? 'font-semibold text-primary' : 'hover:underline'}
                >
                  {item.name} <span className="text-secondary">({item.products_count})</span>
                </Link>
              </li>
            ))}
          </ul>
        </nav>
      </aside>

      <section>
        <div className="flex flex-wrap items-end gap-4">
          <form onSubmit={submitSearch} className="flex flex-1 items-end gap-2">
            <div className="flex-1">
              <label htmlFor="search" className="block text-sm font-medium">
                Rechercher un produit
              </label>
              <input
                key={search}
                id="search"
                name="search"
                type="search"
                minLength={2}
                defaultValue={search}
                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"
              />
            </div>
            <button
              type="submit"
              className="rounded-lg border border-primary px-4 py-2 font-medium text-primary hover:bg-primary/5"
            >
              Rechercher
            </button>
          </form>

          <div>
            <label htmlFor="sort" className="block text-sm font-medium">
              Trier par
            </label>
            <select
              id="sort"
              value={sort}
              onChange={(event) => update({ sort: event.target.value })}
              className="mt-1 rounded-lg border border-gray-300 px-3 py-2"
            >
              {SORTS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="mt-6">
          {products.error && <Alert variant="error">{products.error.message}</Alert>}
          {products.loading && <p className="text-secondary">Chargement…</p>}

          {!products.loading && !products.error && list.length === 0 && (
            <p>Aucun produit ne correspond à votre recherche.</p>
          )}

          <ul className="grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-3">
            {list.map((product) => (
              <ProductTile key={product.id} product={product} />
            ))}
          </ul>

          {meta && meta.last_page > 1 && (
            <nav aria-label="Pagination" className="mt-8 flex items-center justify-center gap-4 text-sm">
              <button
                type="button"
                disabled={page <= 1}
                onClick={() => update({ page: String(page - 1) })}
                className="rounded-lg border border-ink/20 px-3 py-1.5 disabled:opacity-40"
              >
                Page précédente
              </button>
              <span>
                Page {meta.current_page} sur {meta.last_page}
              </span>
              <button
                type="button"
                disabled={page >= meta.last_page}
                onClick={() => update({ page: String(page + 1) })}
                className="rounded-lg border border-ink/20 px-3 py-1.5 disabled:opacity-40"
              >
                Page suivante
              </button>
            </nav>
          )}
        </div>
      </section>
    </div>
  )
}
