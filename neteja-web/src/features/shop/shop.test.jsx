import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from '../../App'
import { api } from '../../lib/api'
import { AuthContext } from '../auth/auth-context'
import { slides } from '../../content/slides'

vi.mock('../../lib/api', () => ({
  api: { get: vi.fn(), post: vi.fn(), request: vi.fn() },
  csrf: vi.fn(),
}))

const emptyCart = {
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

const javelLine = {
  item_id: 7,
  variant_id: 1,
  product_name: 'Eau de Javel',
  product_slug: 'eau-de-javel',
  variant_name: '5 L',
  image_url: null,
  unit_price_fcfa: 3500,
  quantity: 2,
  line_total_fcfa: 7000,
  issue: null,
  stock_left: null,
}

const cartWithJavel = {
  ...emptyCart,
  token: 'a'.repeat(40),
  items_count: 2,
  lines: [javelLine],
  subtotal_fcfa: 7000,
  total_fcfa: 7000,
}

const products = [
  {
    id: 1,
    name: 'Eau de Javel',
    slug: 'eau-de-javel',
    excerpt: '',
    image_url: null,
    category: { name: 'Entretien ménager', slug: 'entretien-menager' },
    price_from_fcfa: 800,
    has_variants: true,
    in_stock: true,
  },
  {
    id: 2,
    name: 'Balai-brosse',
    slug: 'balai-brosse',
    excerpt: '',
    image_url: null,
    category: { name: 'Matériel de nettoyage', slug: 'materiel-de-nettoyage' },
    price_from_fcfa: 3000,
    has_variants: false,
    in_stock: false,
  },
]

const detail = {
  ...products[0],
  description: 'Désinfecte et blanchit.',
  variants: [
    { id: 1, name: '1 L', price_fcfa: 800, in_stock: true, stock_left: null },
    { id: 2, name: '5 L', price_fcfa: 3500, in_stock: true, stock_left: 3 },
  ],
}

/** Simule les réponses de l'API de la boutique. */
function mockApi({ cart = emptyCart } = {}) {
  api.get.mockImplementation(async (url) => {
    if (url === '/api/cart') return { data: { data: cart } }
    if (url === '/api/categories') {
      return { data: { data: [{ name: 'Entretien ménager', slug: 'entretien-menager', products_count: 2 }] } }
    }
    if (url === '/api/delivery-zones') {
      return { data: { data: [{ commune: 'Cocody', fee_fcfa: 2000, estimated_delay: '24 à 48 h' }] } }
    }
    if (url.startsWith('/api/products/')) return { data: { data: detail } }
    if (url.startsWith('/api/products')) {
      return { data: { data: products, meta: { current_page: 1, last_page: 1, total: 2 } } }
    }
    throw new Error(`Appel inattendu : ${url}`)
  })
}

function renderShop(path, user = null) {
  return render(
    <MemoryRouter initialEntries={[path]}>
      <AuthContext.Provider value={{ user, pending: [], loading: false }}>
        <App />
      </AuthContext.Provider>
    </MemoryRouter>,
  )
}

beforeEach(() => {
  vi.resetAllMocks()
  localStorage.clear()
})

describe('boutique', () => {
  it('affiche les produits et les catégories', async () => {
    mockApi()
    renderShop('/boutique')

    expect(await screen.findByRole('link', { name: 'Eau de Javel' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Entretien ménager (2)' })).toBeInTheDocument()
    expect(screen.getByText('Rupture de stock')).toBeInTheDocument()
  })

  it('filtre par catégorie', async () => {
    mockApi()
    const user = userEvent.setup()
    renderShop('/boutique')

    await user.click(await screen.findByRole('link', { name: 'Entretien ménager (2)' }))

    await waitFor(() => expect(api.get).toHaveBeenCalledWith('/api/products?category=entretien-menager'))
  })
})

describe('fiche produit', () => {
  it("ajoute l'article au panier et garde le jeton du visiteur", async () => {
    mockApi()
    api.request.mockResolvedValue({ data: { data: { ...cartWithJavel, token: 'b'.repeat(40) } } })
    const user = userEvent.setup()
    renderShop('/produits/eau-de-javel')

    await user.click(await screen.findByRole('button', { name: 'Ajouter au panier' }))

    expect(await screen.findByText('Ajouté au panier.')).toBeInTheDocument()
    expect(api.request).toHaveBeenCalledWith(
      expect.objectContaining({ method: 'POST', url: '/api/cart/items', data: { variant_id: 1, quantity: 1 } }),
    )
    expect(localStorage.getItem('neteja_cart_token')).toBe('b'.repeat(40))
  })

  it('affiche le message du serveur quand le stock est insuffisant', async () => {
    mockApi()
    api.request.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'x', errors: { quantity: ["Il n'en reste que 3 en stock."] } },
      },
    })
    const user = userEvent.setup()
    renderShop('/produits/eau-de-javel')

    await user.click(await screen.findByRole('button', { name: 'Ajouter au panier' }))

    expect(await screen.findByText("Il n'en reste que 3 en stock.")).toBeInTheDocument()
  })
})

describe('panier', () => {
  it('affiche un panier vide avec un lien vers la boutique', async () => {
    mockApi()
    renderShop('/panier')

    expect(await screen.findByText('Votre panier est vide.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Voir la boutique' })).toBeInTheDocument()
  })

  it('affiche les lignes et le total', async () => {
    mockApi({ cart: cartWithJavel })
    renderShop('/panier')

    expect(await screen.findByText('Eau de Javel')).toBeInTheDocument()
    expect(screen.getAllByText(/7\s000 FCFA/).length).toBeGreaterThan(0)
  })

  it('augmente la quantité', async () => {
    mockApi({ cart: cartWithJavel })
    api.request.mockResolvedValue({
      data: {
        data: {
          ...cartWithJavel,
          items_count: 3,
          lines: [{ ...javelLine, quantity: 3, line_total_fcfa: 10500 }],
          subtotal_fcfa: 10500,
          total_fcfa: 10500,
        },
      },
    })
    const user = userEvent.setup()
    renderShop('/panier')

    await user.click(await screen.findByRole('button', { name: 'Augmenter la quantité de Eau de Javel' }))

    expect(api.request).toHaveBeenCalledWith(
      expect.objectContaining({ method: 'PUT', url: '/api/cart/items/7', data: { quantity: 3 } }),
    )
  })

  it('recalcule la livraison quand on choisit la commune', async () => {
    mockApi({ cart: cartWithJavel })
    const user = userEvent.setup()
    renderShop('/panier')

    await screen.findByRole('option', { name: /Cocody/ })
    await user.selectOptions(screen.getByLabelText('Commune de livraison'), 'Cocody')

    await waitFor(() => expect(api.get).toHaveBeenCalledWith('/api/cart', { params: { commune: 'Cocody' } }))
  })
})

describe('connexion', () => {
  it('verse le panier du visiteur dans celui du compte', async () => {
    mockApi()
    api.post.mockResolvedValue({ data: {} })
    localStorage.setItem('neteja_cart_token', 'c'.repeat(40))

    renderShop('/panier', { id: 1, name: 'Awa Koné', roles: ['client'] })

    await waitFor(() => expect(api.post).toHaveBeenCalledWith('/api/cart/merge'))
    await waitFor(() => expect(localStorage.getItem('neteja_cart_token')).toBeNull())
  })
})

describe('accueil', () => {
  it('affiche le bandeau défilant et passe à la diapositive suivante', async () => {
    mockApi()
    const user = userEvent.setup()
    renderShop('/')

    expect(await screen.findByRole('heading', { name: slides[0].title })).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Diapositive suivante' }))

    expect(await screen.findByRole('heading', { name: slides[1].title })).toBeInTheDocument()
  })

  it('présente les produits dans des rangées défilantes', async () => {
    mockApi()
    renderShop('/')

    expect(await screen.findByRole('heading', { name: 'Nouveautés' })).toBeInTheDocument()
    expect((await screen.findAllByRole('link', { name: 'Eau de Javel' })).length).toBeGreaterThan(0)
  })

  it("garde le menu et la barre d'infos sur la page", async () => {
    mockApi()
    renderShop('/')

    expect(await screen.findByRole('navigation', { name: 'Navigation principale' })).toBeInTheDocument()
    expect(screen.getByText('Livraison à Abidjan en 24 à 48 h')).toBeInTheDocument()
  })
})