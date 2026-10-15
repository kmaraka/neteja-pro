const KEY = 'neteja_cart_token'

export const cartToken = {
  get() {
    try {
      return localStorage.getItem(KEY)
    } catch {
      return null
    }
  },
  set(value) {
    try {
      localStorage.setItem(KEY, value)
    } catch {
      // stockage indisponible : le panier ne survivra pas à la fermeture de l'onglet
    }
  },
  clear() {
    try {
      localStorage.removeItem(KEY)
    } catch {
      // idem
    }
  },
}