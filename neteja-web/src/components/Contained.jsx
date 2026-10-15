import { Outlet, useOutletContext } from 'react-router'

export default function Contained() {
  const context = useOutletContext()

  return (
    <div className="mx-auto w-full max-w-6xl px-4 py-8 md:px-8">
      <Outlet context={context} />
    </div>
  )
}