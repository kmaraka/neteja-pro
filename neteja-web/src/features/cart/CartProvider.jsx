import { useCallback, useEffect, useMemo, useState } from 'react'
import { api } from '../../lib/api'
import { cartToken } from '../../lib/cartToken'
import { useAuth } from '../auth/useAuth'
import { CartContext } from './cart-context'

const EMPTY = {
  token: null,
  items_count: 0,
  lines: [],
  has_issues: false,
  subtotal_fcfa: 0,
  promo: null,
  promo_error: null,
  discount_fcfa: 0,
  delivery: null,
  delivery_error: null,
  delivery_fee_fcfa: null,
  total_fcfa: 0,
}

export function CartProvider({ children }) {
  const { user, loading: authLoading } = useAuth()
  const userId = user?.id ?? null

  const [cart, setCart] = useState(EMPTY)
  const [commune, setCommune] = useState('') // sert à estimer les frais de livraison
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)

  const params = commune ? { commune } : undefined

  /** Enregistre la réponse de l'API. Un visiteur garde son jeton, le serveur fait foi. */
  const applySummary = useCallback(
    (summary) => {
      if (!userId) {
        if (summary.token) cartToken.set(summary.token)
        else cartToken.clear()
      }
      setCart(summary)
    },
    [userId],
  )

  // Au démarrage, à la connexion/déconnexion et au changement de commune : on relit le panier.
  useEffect(() => {
    if (authLoading) return
    let ignore = false

    async function sync() {
      try {
        // Connexion : le panier du visiteur est versé dans celui du compte, une seule fois.
        if (userId && cartToken.get()) {
          try {
            await api.post('/api/cart/merge')
          } catch {
            // panier du visiteur introuvable : rien à fusionner
          }
          cartToken.clear()
        }

        const { data } = await api.get('/api/cart', { params: commune ? { commune } : undefined })
        if (!ignore) applySummary(data.data)
      } catch {
        if (!ignore) setCart(EMPTY)
      } finally {
        if (!ignore) setLoading(false)
      }
    }

    sync()

    return () => {
      ignore = true
    }
  }, [authLoading, userId, commune, applySummary])

  const call = useCallback(
    async (method, url, data) => {
      setBusy(true)
      try {
        const response = await api.request({ method, url, data, params })
        applySummary(response.data.data)

        return response.data.data
      } finally {
        setBusy(false)
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [commune, applySummary],
  )

  const value = useMemo(
    () => ({
      cart,
      commune,
      setCommune,
      loading,
      busy,
      addItem: (variantId, quantity = 1) => call('POST', '/api/cart/items', { variant_id: variantId, quantity }),
      updateItem: (itemId, quantity) => call('PUT', `/api/cart/items/${itemId}`, { quantity }),
      removeItem: (itemId) => call('DELETE', `/api/cart/items/${itemId}`),
      applyPromo: (code) => call('POST', '/api/cart/promo', { code }),
      removePromo: () => call('DELETE', '/api/cart/promo'),
    }),
    [cart, commune, loading, busy, call],
  )

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}