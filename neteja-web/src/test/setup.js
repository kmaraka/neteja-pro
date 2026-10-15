import '@testing-library/jest-dom/vitest'
import { cleanup } from '@testing-library/react'
import { afterEach, vi } from 'vitest'

/** Stockage en mémoire, identique à celui d'un navigateur. */
function memoryStorage() {
  const data = new Map()

  return {
    getItem: (key) => (data.has(String(key)) ? data.get(String(key)) : null),
    setItem: (key, value) => {
      data.set(String(key), String(value))
    },
    removeItem: (key) => {
      data.delete(String(key))
    },
    clear: () => data.clear(),
    key: (index) => [...data.keys()][index] ?? null,
    get length() {
      return data.size
    },
  }
}

// Node 25 expose un `localStorage` natif incomplet qui masque celui de l'environnement de test :
// si la fonction clear() manque, on installe un stockage en mémoire.
for (const name of ['localStorage', 'sessionStorage']) {
  if (typeof globalThis[name]?.clear !== 'function') {
    vi.stubGlobal(name, memoryStorage())
  }
}

// Sans "globals", Testing Library ne nettoie pas le DOM entre deux tests : on le fait nous-mêmes.
afterEach(cleanup)