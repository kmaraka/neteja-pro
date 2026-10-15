import { Link } from 'react-router'
import { site } from '../content/site'

export default function SiteFooter() {
  return (
    <footer className="bg-night text-white/90">
      <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 md:grid-cols-3 md:px-8">
        <div>
          <p className="font-display text-2xl font-semibold text-white">{site.name}</p>
          <p className="mt-2 max-w-xs text-sm">
            Produits d&apos;entretien, matériel et conseils pour la maison, livrés à Abidjan.
          </p>
        </div>

        <nav aria-label="Pied de page" className="space-y-2 text-sm">
          <Link to="/boutique" className="block hover:underline">
            Boutique
          </Link>
          <Link to="/panier" className="block hover:underline">
            Mon panier
          </Link>
          <Link to="/compte" className="block hover:underline">
            Mon compte
          </Link>
        </nav>

        <div className="space-y-2 text-sm">
          <a href={`tel:${site.phoneHref}`} className="block hover:underline">
            {site.phone}
          </a>
          <a href={`mailto:${site.email}`} className="block hover:underline">
            {site.email}
          </a>
          <p>{site.hours}</p>
        </div>
      </div>

      <div className="border-t border-white/15 px-4 py-4 text-sm md:px-8">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
          <p>© {new Date().getFullYear()} {site.name}</p>
          <div className="flex gap-6">
            <Link to="/cgu" className="hover:underline">
              Conditions d&apos;utilisation
            </Link>
            <Link to="/confidentialite" className="hover:underline">
              Confidentialité
            </Link>
          </div>
        </div>
      </div>
    </footer>
  )
}