const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api/v1'
export const backendUrl = apiUrl.replace(/\/api\/v1$/, '')

export function xsrfToken() {
  const token = document.cookie
    .split('; ')
    .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
    ?.split('=')[1]

  return token ? decodeURIComponent(token) : null
}

export async function request(path, options = {}) {
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
