import { useRef } from 'react'
import { Link } from 'react-router'
import { ChevronLeft, ChevronRight } from './icons'
import ProductTile from './ProductTile'

const ITEM = 'w-44 shrink-0 snap-start sm:w-52 lg:w-60'

export default function ProductSlider({ title, products, loading, href }) {
  const track = useRef(null)

  function scroll(direction) {
    const element = track.current
    if (element) element.scrollBy({ left: direction * element.clientWidth * 0.9, behavior: 'smooth' })
  }

  return (
    <section aria-label={title}>
      <div className="mb-4 flex items-end justify-between gap-4">
        <h2 className="font-display text-2xl font-semibold tracking-tight md:text-3xl">{title}</h2>

        <div className="flex items-center gap-4">
          {href && (
            <Link to={href} className="text-sm font-medium text-primary underline-offset-4 hover:underline">
              Voir tout
            </Link>
          )}
          <div className="hidden gap-2 md:flex">
            <button
              type="button"
              aria-label={`${title} : précédents`}
              onClick={() => scroll(-1)}
              className="rounded-full border border-ink/20 p-2 hover:bg-ink/5"
            >
              <ChevronLeft />
            </button>
            <button
              type="button"
              aria-label={`${title} : suivants`}
              onClick={() => scroll(1)}
              className="rounded-full border border-ink/20 p-2 hover:bg-ink/5"
            >
              <ChevronRight />
            </button>
          </div>
        </div>
      </div>

      <ul
        ref={track}
        className="flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
      >
        {loading
          ? Array.from({ length: 5 }, (_, position) => (
              <li key={position} className={ITEM} aria-hidden="true">
                <div className="aspect-square animate-pulse rounded-lg bg-ink/10 motion-reduce:animate-none" />
                <div className="mt-2 h-4 w-3/4 rounded bg-ink/10" />
              </li>
            ))
          : products.map((product) => <ProductTile key={product.id} product={product} className={ITEM} />)}
      </ul>
    </section>
  )
}