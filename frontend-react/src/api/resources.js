import { request } from './client'

export const resources = (filters = {}) => {
  const query = new URLSearchParams(Object.entries(filters).filter(([, value]) => value))
  return request(`/resources${query.size ? `?${query}` : ''}`)
}
