import { useEffect, useState } from 'react'
import { api } from './api'
import { parseError } from './errors'

/**
 * Charge une ressource en GET. `query` est une chaîne comme "category=x&page=2".
 * Renvoie { data, error, loading }.
 */
export function useFetch(path, query = '') {
  const url = query ? `${path}?${query}` : path
  const [state, setState] = useState({ url: null, data: null, error: null })

  useEffect(() => {
    let ignore = false

    api
      .get(url)
      .then(({ data }) => {
        if (!ignore) setState({ url, data, error: null })
      })
      .catch((err) => {
        if (!ignore) setState({ url, data: null, error: parseError(err) })
      })

    return () => {
      ignore = true // une réponse plus ancienne ne doit pas écraser la plus récente
    }
  }, [url])

  const settled = state.url === url

  return {
    data: settled ? state.data : null,
    error: settled ? state.error : null,
    loading: !settled,
  }
}