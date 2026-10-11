import { render, screen } from '@testing-library/react'
import { expect, it } from 'vitest'
import App from './App'

it('affiche le titre Neteja', () => {
  render(<App />)
  expect(screen.getByText(/Neteja/i)).toBeInTheDocument()
})
