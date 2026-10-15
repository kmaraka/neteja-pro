import { Link, useOutletContext } from 'react-router'
import HeroSlider from '../components/HeroSlider'
import { Package, Phone, Shield, Truck } from '../components/icons'
import ProductSlider from '../components/ProductSlider'
import { site } from '../content/site'
import { slides } from '../content/slides'
import { useFetch } from '../lib/useFetch'

const REASSURANCE = [
  { Icon: Truck, title: 'Livraison rapide', text: "24 à 48 h dans les communes d'Abidjan" },
  { Icon: Package, title: 'Payez à la réception', text: 'Le paiement à la livraison est disponible' },
  { Icon: Shield, title: 'Paiement sécurisé', text: 'Carte bancaire et Mobile Money' },
  { Icon: Phone, title: 'Conseils au téléphone', text: site.hours },
]

const TILE_THEMES = [
  'bg-night text-white',
  'bg-primary text-white',
  'bg-accent text-night',
  'bg-citron text-night',
]

export default function HomePage() {
  const { categories } = useOutletContext()
  const newest = useFetch('/api/products', 'per_page=10')
  const cheapest = useFetch('/api/products', 'sort=price_asc&per_page=10')

  return (
    <>
      <h1 className="sr-only">Neteja : produits d&apos;entretien, matériel et jardinage livrés à Abidjan</h1>

      <HeroSlider slides={slides} />

      <section aria-label="Nos engagements" className="border-b border-ink/10 bg-white">
        <ul className="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-4 py-6 md:grid-cols-4 md:px-8">
          {REASSURANCE.map(({ Icon, title, text }) => (
            <li key={title} className="flex items-start gap-3">
              <span className="rounded-full bg-accent/30 p-2.5 text-primary">
                <Icon />
              </span>
              <div>
                <p className="font-medium">{title}</p>
                <p className="text-sm text-secondary">{text}</p>
              </div>
            </li>
          ))}
        </ul>
      </section>

      <div className="mx-auto max-w-6xl space-y-14 px-4 py-12 md:px-8">
        {categories.length > 0 && (
          <section aria-label="Catégories">
            <h2 className="mb-4 font-display text-2xl font-semibold tracking-tight md:text-3xl">
              Nos catégories
            </h2>
            <ul className="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
              {categories.map((category, position) => (
                <li key={category.slug}>
                  <Link
                    to={`/boutique?category=${encodeURIComponent(category.slug)}`}
                    className={`relative flex h-32 flex-col justify-end overflow-hidden rounded-xl p-4 md:h-40 ${
                      TILE_THEMES[position % TILE_THEMES.length]
                    }`}
                  >
                    <span aria-hidden="true" className="absolute -right-6 -top-6 size-28 rounded-full bg-white/15" />
                    <span className="relative font-display text-lg font-semibold leading-tight">{category.name}</span>
                    <span className="relative text-sm opacity-85">
                      {category.products_count} produit{category.products_count > 1 ? 's' : ''}
                    </span>
                  </Link>
                </li>
              ))}
            </ul>
          </section>
        )}

        <ProductSlider title="Nouveautés" products={newest.data?.data ?? []} loading={newest.loading} href="/boutique" />

        <ProductSlider
          title="À petits prix"
          products={cheapest.data?.data ?? []}
          loading={cheapest.loading}
          href="/boutique?sort=price_asc"
        />
      </div>
    </>
  )
}