import { request, xsrfToken } from './client'

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
