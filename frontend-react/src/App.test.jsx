import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('./api', () => ({
  addFollowup: vi.fn(),
  createReport: vi.fn(),
  createShift: vi.fn(),
  currentUser: vi.fn(() => new Promise(() => {})),
  evidencePreviewUrl: vi.fn(),
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
import * as api from './api'

afterEach(() => {
  cleanup()
  window.history.replaceState(null, '', '/')
})

describe('central access', () => {
  it('does not render an alternative login form while checking the central session', () => {
    render(<App />)

    expect(screen.getByText('Validando sesión central…')).toBeInTheDocument()
    expect(screen.queryByLabelText('Usuario')).not.toBeInTheDocument()
  })
})

const resource = (id, name, status = 'available') => ({ id, name, status, resource_type: 'Bomba' })

function deferred() {
  let resolve
  let reject
  const promise = new Promise((res, rej) => { resolve = res; reject = rej })
  return { promise, resolve, reject }
}

describe('updates without replacing the page', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    api.currentUser.mockResolvedValue({ user: { id: 1, name: 'Vecino de prueba', role: 'neighbor' } })
    api.sectors.mockResolvedValue({ data: [{ id: 1, name: 'Sector Norte' }] })
    api.reports.mockResolvedValue({ data: [] })
    api.shifts.mockResolvedValue({ data: [] })
    api.resources.mockResolvedValue({ data: [resource(1, 'Bomba inicial')] })
  })

  async function openApp() {
    render(<App />)
    await screen.findByRole('heading', { name: 'Bomba inicial' })
    await waitFor(() => expect(screen.queryByText('Cargando información…')).not.toBeInTheDocument())
  }

  it('keeps the form and page mounted while filtering only resources', async () => {
    await openApp()
    const pending = deferred()
    api.resources.mockReturnValueOnce(pending.promise)
    const formInput = screen.getByLabelText('Tipo de problema')
    const heading = screen.getByRole('heading', { name: 'Recursos comunitarios' })
    fireEvent.change(formInput, { target: { value: 'Fuga pendiente de enviar' } })
    const filter = screen.getByLabelText('Estado')
    filter.focus()
    fireEvent.change(filter, { target: { value: 'maintenance' } })

    expect(screen.getByRole('heading', { name: 'Recursos comunitarios' })).toBe(heading)
    expect(screen.getByLabelText('Tipo de problema')).toBe(formInput)
    expect(formInput).toHaveValue('Fuga pendiente de enviar')
    expect(filter).toHaveFocus()
    expect(screen.queryByText('Validando sesión central…')).not.toBeInTheDocument()
    expect(screen.getByText('Actualizando recursos…')).toBeInTheDocument()
    expect(api.sectors).toHaveBeenCalledTimes(1)
    expect(api.reports).toHaveBeenCalledTimes(1)
    expect(api.shifts).toHaveBeenCalledTimes(1)

    await act(async () => pending.resolve({ data: [resource(2, 'Bomba en reparación', 'maintenance')] }))
    expect(await screen.findByRole('heading', { name: 'Bomba en reparación' })).toBeInTheDocument()
    expect(screen.queryByText('Actualizando recursos…')).not.toBeInTheDocument()
    expect(formInput).toHaveValue('Fuga pendiente de enviar')
  })

  it('keeps the latest filter when earlier requests finish later', async () => {
    await openApp()
    const olderRequest = deferred()
    api.resources.mockReturnValueOnce(olderRequest.promise)
    api.resources.mockResolvedValueOnce({ data: [resource(3, 'Bomba inactiva', 'inactive')] })
    fireEvent.change(screen.getByLabelText('Estado'), { target: { value: 'maintenance' } })
    fireEvent.change(screen.getByLabelText('Estado'), { target: { value: 'inactive' } })
    await screen.findByRole('heading', { name: 'Bomba inactiva' })
    await act(async () => olderRequest.resolve({ data: [resource(2, 'Resultado anterior', 'maintenance')] }))
    expect(screen.getByRole('heading', { name: 'Bomba inactiva' })).toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Resultado anterior' })).not.toBeInTheDocument()
  })

  it('preserves the page and existing results if filtering fails', async () => {
    await openApp()
    api.resources.mockRejectedValueOnce(new Error('No se pudo actualizar el filtro'))
    fireEvent.change(screen.getByLabelText('Estado'), { target: { value: 'maintenance' } })
    expect(await screen.findByRole('alert')).toHaveTextContent('No se pudo actualizar el filtro')
    expect(screen.getByRole('heading', { name: 'Bomba inicial' })).toBeInTheDocument()
    expect(screen.queryByText('Actualizando recursos…')).not.toBeInTheDocument()
  })

  it('opens the reports anchor below shifts after initial data loads', async () => {
    window.history.replaceState(null, '', '/#reportes')
    const scrollIntoView = vi.fn()
    const originalScroll = Element.prototype.scrollIntoView
    Element.prototype.scrollIntoView = scrollIntoView
    try {
      await openApp()
      const section = screen.getByRole('heading', { name: 'Reportar un problema' }).closest('section')
      expect(section).toHaveAttribute('id', 'reportes')
      expect(section.previousElementSibling).toHaveAttribute('id', 'turnos')
      expect(screen.getByRole('link', { name: 'Reportes' })).toHaveAttribute('href', '#reportes')
      expect(scrollIntoView).toHaveBeenCalled()
      expect(scrollIntoView.mock.instances).toContain(section)
    } finally {
      Element.prototype.scrollIntoView = originalScroll
    }
  })

  it('requests a private temporary S3 URL and previews report evidence', async () => {
    api.reports.mockResolvedValue({ data: [{
      id: 42,
      category: 'Fuga de agua',
      description: 'Fuga demo junto a la entrada comunitaria.',
      status: 'pending',
      priority: 'normal',
      sector: { id: 1, name: 'Sector Norte' },
      followups: [],
      evidences: [{ id: 7, original_name: 'fuga.jpg', mime_type: 'image/jpeg', size_bytes: 1024 }],
    }] })
    api.evidencePreviewUrl.mockResolvedValue({ url: 'https://s3.example.test/temporary-image', expires_at: 'later' })
    await openApp()

    fireEvent.click(screen.getByRole('button', { name: 'Visualizar evidencia' }))

    expect(await screen.findByRole('dialog', { name: 'Vista previa de fuga.jpg' })).toBeInTheDocument()
    expect(await screen.findByRole('img', { name: 'fuga.jpg' })).toHaveAttribute('src', 'https://s3.example.test/temporary-image')
    expect(api.evidencePreviewUrl).toHaveBeenCalledWith(42, 7)
    fireEvent.click(screen.getByRole('button', { name: 'Cerrar vista previa' }))
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })
})
