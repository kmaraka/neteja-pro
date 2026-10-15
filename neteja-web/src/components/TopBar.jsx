import { site } from '../content/site'
import { Clock, Phone, Truck } from './icons'

export default function TopBar() {
  return (
    <div className="bg-night text-white">
      <div className="mx-auto flex h-9 max-w-6xl items-center justify-between gap-4 px-4 text-sm md:px-8">
        <p className="flex min-w-0 items-center gap-2">
          <Truck className="size-4 shrink-0 text-citron" />
          <span className="truncate">{site.delivery}</span>
        </p>

        <div className="hidden items-center gap-6 md:flex">
          <a href={`tel:${site.phoneHref}`} className="flex items-center gap-2 hover:underline">
            <Phone className="size-4 text-citron" />
            {site.phone}
          </a>
          <p className="flex items-center gap-2">
            <Clock className="size-4 text-citron" />
            {site.hours}
          </p>
        </div>
      </div>
    </div>
  )
}