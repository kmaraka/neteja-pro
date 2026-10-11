import axios from 'axios'

export const api = axios.create({
  // Vide en développement (le proxy Vite relaie). En production : l'URL de l'API.
  baseURL: import.meta.env.VITE_API_URL ?? '',
  withCredentials: true, // envoie et reçoit les cookies de session
  withXSRFToken: true, // recopie le cookie XSRF-TOKEN dans l'en-tête X-XSRF-TOKEN
  headers: {
    Accept: 'application/json', // sans cela Laravel répond par des redirections HTML
    'X-Requested-With': 'XMLHttpRequest',
  },
})

/** À appeler avant la connexion : pose le cookie CSRF et la session. */
export const csrf = () => api.get('/sanctum/csrf-cookie')

// 419 = jeton CSRF expiré : on le renouvelle et on rejoue la requête une fois.
api.interceptors.response.use(undefined, async (error) => {
  const { config, response } = error
  if (response?.status === 419 && config && !config._retried) {
    config._retried = true
    await csrf()
    return api(config)
  }
  return Promise.reject(error)
})