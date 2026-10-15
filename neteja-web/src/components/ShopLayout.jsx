import { Outlet } from 'react-router'
import { useFetch } from '../lib/useFetch'
import SiteFooter from './SiteFooter'
import SiteHeader from './SiteHeader'
import TopBar from './TopBar'

export default function ShopLayout() {
  const categories = useFetch('/api/categories')
  const list = categories.data?.data ?? []

  return (
    <div className="flex min-h-screen flex-col">
      {/* Barre d'infos (36 px) + menu (64 px) : fixes en haut de l'écran. */}
      <div className="fixed inset-x-0 top-0 z-40">
        <TopBar />
        <SiteHeader categories={list} />
      </div>

      <div className="flex-1 pt-[6.25rem]">
        <Outlet context={{ categories: list }} />
      </div>

      <SiteFooter />
    </div>
  )
}