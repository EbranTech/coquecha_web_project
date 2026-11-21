import { backendUrl, request, xsrfToken } from './client'

export async function login(username, password) {
  await fetch(`${backendUrl}/sanctum/csrf-cookie`, { credentials: 'include' })

  return request('/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
    body: JSON.stringify({ username, password }),
  })
}

export const currentUser = () => request('/auth/me')

export const logout = () => request('/auth/logout', {
  method: 'POST',
  headers: { 'X-XSRF-TOKEN': xsrfToken() },
})
