import { render, screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'

vi.mock('./api', () => ({
  addFollowup: vi.fn(),
  createReport: vi.fn(),
  createShift: vi.fn(),
  currentUser: vi.fn(() => Promise.reject(new Error('Unauthenticated'))),
  login: vi.fn(),
  logout: vi.fn(),
  reports: vi.fn(),
  resources: vi.fn(),
  saveShiftResult: vi.fn(),
  sectors: vi.fn(),
  shifts: vi.fn(),
  updateShift: vi.fn(),
  updateShiftStatus: vi.fn(),
  uploadEvidences: vi.fn(),
}))

import App from './App'

describe('central access', () => {
  it('shows the central login path when no active session exists', async () => {
    render(<App />)

    expect(await screen.findByRole('link', { name: 'acceso central' }))
      .toHaveAttribute('href', 'http://localhost:8080/login')
  })
})
