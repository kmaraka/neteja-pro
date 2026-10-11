import { useState } from 'react'
import { Link } from 'react-router'

const KEY = 'neteja_cookie_consent'

function readChoice() {
  try {
    return localStorage.getItem(KEY)
  } catch {
    return null
  }
}

export default function CookieBanner() {
  const [choice, setChoice] = useState(readChoice)

  if (choice) return null

  const save = (value) => {
    try {
      localStorage.setItem(KEY, value)
    } catch {
      // stockage indisponible : le choix vaut pour cette visite seulement
    }
    setChoice(value)
  }

  return (
    <div className="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white p-4 shadow-lg">
      <div className="mx-auto flex max-w-3xl flex-col gap-3 sm:flex-row sm:items-center">
        <p className="flex-1 text-sm text-gray-700">
          Ce site utilise des cookies nécessaires à son fonctionnement. Les cookies de mesure
          d&apos;audience ne seront activés qu&apos;avec votre accord.{' '}
          <Link to="/confidentialite" className="text-primary underline">
            En savoir plus
          </Link>
        </p>
        <div className="flex gap-2">
          <button
            onClick={() => save('refused')}
            className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Refuser
          </button>
          <button
            onClick={() => save('accepted')}
            className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
          >
            Accepter
          </button>
        </div>
      </div>
    </div>
  )
}