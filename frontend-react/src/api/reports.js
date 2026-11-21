import { request, xsrfToken } from './client'

export const reports = () => request('/reports')

export const evidencePreviewUrl = (reportId, evidenceId) =>
  request(`/reports/${reportId}/evidences/${evidenceId}/download`)

export const createReport = (payload) => request('/reports', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
  body: JSON.stringify(payload),
})

export const uploadEvidences = (reportId, files) => {
  const body = new FormData()
  Array.from(files).forEach((file) => body.append('files[]', file))
  return request(`/reports/${reportId}/evidences`, {
    method: 'POST',
    headers: { 'X-XSRF-TOKEN': xsrfToken() },
    body,
  })
}

export const addFollowup = (reportId, payload) => request(`/reports/${reportId}/followups`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
  body: JSON.stringify(payload),
})
