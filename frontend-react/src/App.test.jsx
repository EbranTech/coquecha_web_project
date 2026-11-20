import { render, screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'

vi.mock('./api', () => ({
  addFollowup: vi.fn(),
  createReport: vi.fn(),
  createShift: vi.fn(),
  currentUser: vi.fn(() => new Promise(() => {})),
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
  it('does not render an alternative login form while checking the central session', () => {
    render(<App />)

    expect(screen.getByText('Validando sesión central…')).toBeInTheDocument()
    expect(screen.queryByLabelText('Usuario')).not.toBeInTheDocument()
  })
})
