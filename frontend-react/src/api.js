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
  const response = await fetch(`${apiUrl}${path}`, {
    credentials: 'include',
    headers: { Accept: 'application/json', ...options.headers },
    ...options,
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(body.message ?? 'No fue posible completar la solicitud.')
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
