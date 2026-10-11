import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from '../../App'
import { api, csrf } from '../../lib/api'
import { AuthProvider } from './AuthProvider'

// Mock global de localStorage pour éviter le crash de localStorage.clear
global.localStorage = {
  clear: vi.fn(),
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
}

vi.mock('../../lib/api', () => ({
  api: { get: vi.fn(), post: vi.fn(), delete: vi.fn() },
  csrf: vi.fn(),
}))

const baseUser = {
  id: 1,
  name: 'Awa Koné',
  email: 'awa@example.com',
  phone: null,
  email_verified: true,
  must_change_password: false,
  is_super_admin: false,
  roles: ['client'],
  permissions: [],
}

/** Simule les réponses de /api/auth/me et /api/consents/pending. */
function mockSession({ user = null, pending = [] } = {}) {
  api.get.mockImplementation(async (url) => {
    if (url === '/api/auth/me') {
      if (!user) throw { response: { status: 401, data: { message: 'Unauthenticated.' } } }
      return { data: { user } }
    }
    if (url === '/api/consents/pending') return { data: { pending } }
    throw new Error(`Appel inattendu : ${url}`)
  })
}

function renderApp(path) {
  return render(
    <MemoryRouter initialEntries={[path]}>
      <AuthProvider>
        <App />
      </AuthProvider>
    </MemoryRouter>,
  )
}

beforeEach(() => {
  vi.resetAllMocks()
  csrf.mockResolvedValue({})
  localStorage.clear()
})

describe('accès aux pages', () => {
  it('redirige un visiteur vers la connexion', async () => {
    mockSession()
    renderApp('/compte')

    expect(await screen.findByRole('heading', { name: 'Connexion' })).toBeInTheDocument()
  })

  it('force le changement du mot de passe temporaire', async () => {
    mockSession({ user: { ...baseUser, must_change_password: true } })
    renderApp('/compte')

    expect(
      await screen.findByRole('heading', { name: 'Changer mon mot de passe' }),
    ).toBeInTheDocument()
  })

  it('laisse changer le mot de passe même avec des conditions en attente', async () => {
    mockSession({ user: baseUser, pending: ['cgu'] })
    renderApp('/changer-mot-de-passe')

    expect(
      await screen.findByRole('heading', { name: 'Changer mon mot de passe' }),
    ).toBeInTheDocument()
  })

  it("demande d'accepter les conditions en attente", async () => {
    mockSession({ user: baseUser, pending: ['cgu'] })
    renderApp('/compte')

    expect(
      await screen.findByRole('heading', { name: "Conditions d'utilisation" }),
    ).toBeInTheDocument()
  })

  it('affiche le compte quand tout est en règle', async () => {
    mockSession({ user: baseUser })
    renderApp('/compte')

    expect(await screen.findByText('awa@example.com')).toBeInTheDocument()
  })
})

describe('connexion', () => {
  it("affiche l'erreur du serveur sous le champ concerné", async () => {
    mockSession()
    api.post.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'Invalid', errors: { login: ['Identifiants incorrects.'] } },
      },
    })
    const user = userEvent.setup()
    renderApp('/connexion')

    await user.type(await screen.findByLabelText('Email ou téléphone'), 'a@b.com')
    await user.type(screen.getByLabelText('Mot de passe'), 'Mauvais123')
    await user.click(screen.getByRole('button', { name: 'Se connecter' }))

    expect(await screen.findByText('Identifiants incorrects.')).toBeInTheDocument()
  })

  it('propose de renvoyer le mail quand le compte est non vérifié', async () => {
    mockSession()
    api.post.mockRejectedValue({
      response: { status: 403, data: { code: 'email_not_verified', message: 'x' } },
    })
    const user = userEvent.setup()
    renderApp('/connexion')

    await user.type(await screen.findByLabelText('Email ou téléphone'), 'a@b.com')
    await user.type(screen.getByLabelText('Mot de passe'), 'Secret123')
    await user.click(screen.getByRole('button', { name: 'Se connecter' }))

    expect(
      await screen.findByRole('button', { name: /renvoyer l'email de vérification/i }),
    ).toBeInTheDocument()
  })
})
