const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api/v1'
const backendUrl = apiUrl.replace(/\/api\/v1$/, '')

function xsrfToken() {
  const token = document.cookie
    .split('; ')
    .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
    ?.split('=')[1]

  return token ? decodeURIComponent(token) : null
}

async function request(path, options = {}) {
  let response

  try {
    response = await fetch(`${apiUrl}${path}`, {
      credentials: 'include',
      headers: { Accept: 'application/json', ...options.headers },
      ...options,
    })
  } catch {
    throw new Error('No fue posible comunicarse con el servidor. Verifica que Laravel esté activo en http://localhost:8080.')
  }

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    const error = new Error(body.message ?? 'No fue posible completar la solicitud.')
    error.code = body.code
    throw error
  }

  return response.status === 204 ? null : response.json()
}

export async function login(username, password) {
  await fetch(`${backendUrl}/sanctum/csrf-cookie`, { credentials: 'include' })

  return request('/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
    body: JSON.stringify({ username, password }),
  })
}

export const currentUser = () => request('/auth/me')
export const logout = () => request('/auth/logout', { method: 'POST', headers: { 'X-XSRF-TOKEN': xsrfToken() } })
export const sectors = () => request('/sectors')
export const resources = (filters = {}) => {
  const query = new URLSearchParams(Object.entries(filters).filter(([, value]) => value))
  return request(`/resources${query.size ? `?${query}` : ''}`)
}
export const reports = () => request('/reports')
export const createReport = (payload) => request('/reports', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
  body: JSON.stringify(payload),
})
export const uploadEvidences = (reportId, files) => {
  const body = new FormData()
  Array.from(files).forEach((file) => body.append('files[]', file))
  return request(`/reports/${reportId}/evidences`, { method: 'POST', headers: { 'X-XSRF-TOKEN': xsrfToken() }, body })
}
export const addFollowup = (reportId, payload) => request(`/reports/${reportId}/followups`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
  body: JSON.stringify(payload),
})
export const shifts = (filters = {}) => {
  const query = new URLSearchParams(Object.entries(filters).filter(([, value]) => value))
  return request(`/shifts${query.size ? `?${query}` : ''}`)
}
export const createShift = (payload) => request('/shifts', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
  body: JSON.stringify(payload),
})
export const updateShift = (shiftId, payload) => request(`/shifts/${shiftId}`, {
  method: 'PUT',
  headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
  body: JSON.stringify(payload),
})
export const updateShiftStatus = (shiftId, status) => request(`/shifts/${shiftId}/status`, {
  method: 'PATCH',
  headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
  body: JSON.stringify({ status }),
})
export const saveShiftResult = (shiftId, payload) => request(`/shifts/${shiftId}/result`, {
  method: 'PUT',
  headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
  body: JSON.stringify(payload),
})
