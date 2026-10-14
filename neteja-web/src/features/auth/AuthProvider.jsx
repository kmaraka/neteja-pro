import { useCallback, useEffect, useMemo, useState } from 'react'
import { api, csrf } from '../../lib/api'
import { AuthContext } from './auth-context'

const GUEST = { user: null, pending: [] }

/** Interroge l'API : renvoie l'utilisateur connecté et ses consentements en attente. */
async function fetchSession() {
  try {
    const { data } = await api.get('/api/auth/me')
    const { data: consents } = await api.get('/api/consents/pending')
    return { user: data.user, pending: consents.pending }
  } catch {
    return GUEST // 401 : visiteur non connecté
  }
}

export function AuthProvider({ children }) {
  const [session, setSession] = useState(GUEST)
  const [loading, setLoading] = useState(true)

  const apply = useCallback((next) => {
    setSession(next)
    setLoading(false)
  }, [])

  const refresh = useCallback(async () => apply(await fetchSession()), [apply])
  const reset = useCallback(() => apply(GUEST), [apply])

  // Au démarrage : « suis-je déjà connecté ? »
  useEffect(() => {
    let ignore = false
    fetchSession().then((next) => {
      if (!ignore) apply(next)
    })
    return () => {
      ignore = true // le composant a été démonté : on abandonne la réponse
    }
  }, [apply])

  const value = useMemo(
    () => ({
      user: session.user,
      pending: session.pending,
      loading,
      refresh,

      async login({ login, password, remember }) {
        await csrf()
        await api.post('/api/auth/login', { login, password, remember })
        await refresh()
      },

      async register(payload) {
        await csrf()
        const { data } = await api.post('/api/auth/register', payload)
        return data
      },

      async logout() {
        try {
          await api.post('/api/auth/logout')
        } finally {
          reset()
        }
      },

      async acceptConsents(types) {
        await api.post('/api/consents', { types })
        await refresh()
      },

      async deleteAccount() {
        await api.delete('/api/account')
        reset()
      },
    }),
    [session, loading, refresh, reset],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
