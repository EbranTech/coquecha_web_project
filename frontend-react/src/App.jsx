import { useEffect, useState } from 'react'
import { addFollowup, createReport, createShift, currentUser, logout, reports, resources, saveShiftResult, sectors, shifts, updateShift, updateShiftStatus, uploadEvidences } from './api'
import './App.css'

const initialReport = { sector_id: '', category: '', description: '', priority: 'normal' }
const initialShift = { sector_id: '', starts_at: '', ends_at: '', notes: '', resource_ids: [] }

function App() {
  const [user, setUser] = useState(null)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [loading, setLoading] = useState(true)
  const [resourceStatus, setResourceStatus] = useState('')
  const [sectorList, setSectorList] = useState([])
  const [resourceList, setResourceList] = useState([])
  const [reportList, setReportList] = useState([])
  const [reportForm, setReportForm] = useState(initialReport)
  const [files, setFiles] = useState([])
  const [submittingReport, setSubmittingReport] = useState(false)
  const [followups, setFollowups] = useState({})
  const [shiftList, setShiftList] = useState([])
  const [shiftForm, setShiftForm] = useState(initialShift)
  const [shiftResourceList, setShiftResourceList] = useState([])
  const [submittingShift, setSubmittingShift] = useState(false)
  const [editingShiftId, setEditingShiftId] = useState(null)
  const [shiftResults, setShiftResults] = useState({})

  useEffect(() => {
    currentUser()
      .then(({ user: activeUser }) => setUser(activeUser))
      .catch(() => window.location.replace('http://localhost:8080/login'))
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    if (!user) return
    setLoading(true)
    Promise.all([sectors(), resources({ status: resourceStatus }), reports(), shifts()])
      .then(([sectorResponse, resourceResponse, reportResponse, shiftResponse]) => {
        setSectorList(sectorResponse.data)
        setResourceList(resourceResponse.data)
        setReportList(reportResponse.data)
        setShiftList(shiftResponse.data)
      })
      .catch((requestError) => setError(requestError.message))
      .finally(() => setLoading(false))
  }, [user, resourceStatus])

  useEffect(() => {
    if (!shiftForm.sector_id) {
      setShiftResourceList([])
      return
    }

    resources({ sector_id: shiftForm.sector_id, status: 'available' })
      .then((response) => setShiftResourceList(response.data))
      .catch((requestError) => setError(requestError.message))
  }, [shiftForm.sector_id])

  async function handleLogout() {
    setError('')
    try {
      await logout()
    } catch {
      setNotice('La sesión se cerró en esta vista. No fue posible confirmar el cierre con el servidor.')
    } finally {
      setUser(null)
      setSectorList([])
      setResourceList([])
      setReportList([])
      setResourceStatus('')
      setShiftList([])
    }
  }

  async function handleReportSubmit(event) {
    event.preventDefault()
    setSubmittingReport(true)
    setError('')
    setNotice('')
    try {
      const response = await createReport({ ...reportForm, sector_id: Number(reportForm.sector_id) })
      if (files.length) await uploadEvidences(response.data.id, files)
      const refreshedReports = await reports()
      setReportList(refreshedReports.data)
      setReportForm(initialReport)
      setFiles([])
      event.target.reset()
      setNotice('El reporte fue enviado. Puedes consultar su avance en el historial.')
    } catch (requestError) {
      setError(requestError.message)
    } finally {
      setSubmittingReport(false)
    }
  }

  async function handleFollowup(reportId) {
    const form = followups[reportId] ?? {}
    if (!form.message?.trim()) return
    setError('')
    try {
      const response = await addFollowup(reportId, form)
      setReportList((items) => items.map((report) => report.id === reportId ? response.data : report))
      setFollowups((items) => ({ ...items, [reportId]: { message: '', new_status: '' } }))
      setNotice('El seguimiento fue registrado.')
    } catch (requestError) {
      setError(requestError.message)
    }
  }

  async function handleShiftSubmit(event) {
    event.preventDefault()
    setSubmittingShift(true)
    setError('')
    setNotice('')
    try {
      const payload = {
        ...shiftForm,
        sector_id: Number(shiftForm.sector_id),
        resource_ids: shiftForm.resource_ids.map(Number),
      }
      const response = editingShiftId ? await updateShift(editingShiftId, payload) : await createShift(payload)
      setShiftList((items) => (editingShiftId
        ? items.map((shift) => shift.id === editingShiftId ? response.data : shift)
        : [...items, response.data]
      ).sort((first, second) => first.starts_at.localeCompare(second.starts_at)))
      setShiftForm(initialShift)
      setShiftResourceList([])
      setEditingShiftId(null)
      event.target.reset()
      setNotice(editingShiftId ? 'El turno fue actualizado correctamente.' : 'El turno fue programado correctamente.')
    } catch (requestError) {
      setError(requestError.code === 'RESOURCE_TIME_CONFLICT' ? 'No se puede programar: uno de los recursos ya está reservado en ese horario.' : requestError.message)
    } finally {
      setSubmittingShift(false)
    }
  }

  function startShiftEdit(shift) {
    setEditingShiftId(shift.id)
    setShiftForm({
      sector_id: String(shift.sector.id),
      starts_at: shift.starts_at.slice(0, 16),
      ends_at: shift.ends_at.slice(0, 16),
      notes: shift.notes ?? '',
      resource_ids: shift.resources.map((resource) => resource.id),
    })
    setNotice('Edita los datos del turno y guarda los cambios.')
    document.getElementById('turnos')?.scrollIntoView({ behavior: 'smooth' })
  }

  function cancelShiftEdit() {
    setEditingShiftId(null)
    setShiftForm(initialShift)
    setShiftResourceList([])
  }

  async function handleShiftStatus(shiftId, status) {
    setError('')
    try {
      const response = await updateShiftStatus(shiftId, status)
      setShiftList((items) => items.map((shift) => shift.id === shiftId ? response.data : shift))
      setNotice('El estado del turno fue actualizado.')
    } catch (requestError) {
      setError(requestError.message)
    }
  }

  async function handleShiftResult(shiftId) {
    const result = shiftResults[shiftId] ?? {}
    if (!result.delivered_volume) return
    setError('')
    try {
      const response = await saveShiftResult(shiftId, { delivered_volume: Number(result.delivered_volume), volume_unit: result.volume_unit || 'litros' })
      setShiftList((items) => items.map((shift) => shift.id === shiftId ? response.data : shift))
      setNotice('El volumen distribuido fue registrado.')
    } catch (requestError) {
      setError(requestError.message)
    }
  }

  if (loading || !user) return <main className="page"><p>Validando sesión central…</p></main>

  const canManageReports = user.role === 'admin' || user.role === 'coordinator'
  const canManageShifts = user.role === 'admin' || user.role === 'coordinator'

  return (
    <main className="page">
      <header className="topbar"><div><p className="eyebrow">Coquechas</p><h1>Recursos comunitarios</h1></div><div className="user-actions"><nav className="module-nav"><a href="#turnos">Turnos</a><a href="#reportes">Reportes</a>{user.role === 'admin' && <a href="http://localhost:8080/admin">Administración</a>}{canManageShifts && <a href="http://localhost:3000">Indicadores</a>}</nav><span>{user.name} · {user.role}</span><button className="secondary" onClick={handleLogout} type="button">Salir</button></div></header>
      {error && <p className="error">{error}</p>}{notice && <p className="notice">{notice}</p>}
      <section className="summary"><article><strong>{sectorList.length}</strong><span>Sectores activos</span></article><article><strong>{resourceList.length}</strong><span>Recursos mostrados</span></article><article><strong>{reportList.length}</strong><span>Reportes visibles</span></article></section>
      <section className="catalog" id="turnos">
        <div className="section-heading"><div><p className="eyebrow">Distribución</p><h2>{editingShiftId ? 'Editar turno de agua' : 'Turnos de agua'}</h2></div></div>
        {canManageShifts && <form className="report-form" onSubmit={handleShiftSubmit}>
          <label>Sector<select value={shiftForm.sector_id} onChange={(event) => setShiftForm({ ...shiftForm, sector_id: event.target.value, resource_ids: [] })} required><option value="">Selecciona un sector</option>{sectorList.map((sector) => <option key={sector.id} value={sector.id}>{sector.name}</option>)}</select></label>
          <label>Inicio<input type="datetime-local" value={shiftForm.starts_at} onChange={(event) => setShiftForm({ ...shiftForm, starts_at: event.target.value })} required /></label>
          <label>Fin<input type="datetime-local" value={shiftForm.ends_at} onChange={(event) => setShiftForm({ ...shiftForm, ends_at: event.target.value })} required /></label>
          <label className="full-width">Recursos disponibles<div className="choices">{shiftResourceList.map((resource) => <label key={resource.id}><input type="checkbox" checked={shiftForm.resource_ids.includes(resource.id)} onChange={(event) => setShiftForm({ ...shiftForm, resource_ids: event.target.checked ? [...shiftForm.resource_ids, resource.id] : shiftForm.resource_ids.filter((id) => id !== resource.id) })} /> {resource.name} ({resource.resource_type})</label>)}</div></label>
          <label className="full-width">Notas<textarea value={shiftForm.notes} onChange={(event) => setShiftForm({ ...shiftForm, notes: event.target.value })} maxLength="2000" /></label>
          <div className="form-actions"><button disabled={submittingShift} type="submit">{submittingShift ? 'Guardando…' : editingShiftId ? 'Guardar cambios' : 'Programar turno'}</button>{editingShiftId && <button className="secondary" onClick={cancelShiftEdit} type="button">Cancelar edición</button>}</div>
        </form>}
        <div className="cards">{shiftList.map((shift) => <article className="card report-card" key={shift.id}><span className={`badge ${shift.status}`}>{shift.status}</span><h3>{shift.sector.name}</h3><p>{new Date(shift.starts_at).toLocaleString()} – {new Date(shift.ends_at).toLocaleTimeString()}</p><small>{shift.resources.map((resource) => resource.name).join(', ') || 'Sin recursos'}</small>{shift.result && <p>Volumen distribuido: {shift.result.delivered_volume} {shift.result.volume_unit}</p>}{canManageShifts && <div className="shift-actions">{shift.status === 'planned' && <><button type="button" onClick={() => startShiftEdit(shift)}>Editar</button><button type="button" onClick={() => handleShiftStatus(shift.id, 'in_progress')}>Iniciar</button><button className="secondary" type="button" onClick={() => handleShiftStatus(shift.id, 'cancelled')}>Cancelar</button></>}{shift.status === 'in_progress' && <button type="button" onClick={() => handleShiftStatus(shift.id, 'completed')}>Completar</button>}{shift.status === 'completed' && !shift.result && <div className="result-form"><input aria-label="Volumen distribuido" type="number" min="0.01" placeholder="Volumen" value={shiftResults[shift.id]?.delivered_volume ?? ''} onChange={(event) => setShiftResults({ ...shiftResults, [shift.id]: { delivered_volume: event.target.value } })} /><button type="button" onClick={() => handleShiftResult(shift.id)}>Guardar volumen</button></div>}</div>}</article>)}</div>
        {!loading && !shiftList.length && <p className="empty">Aún no hay turnos programados.</p>}
      </section>
      <section className="catalog"><div className="section-heading"><div><p className="eyebrow">Problemas comunitarios</p><h2>Reportar un problema</h2></div></div><form className="report-form" onSubmit={handleReportSubmit}><label>Sector<select value={reportForm.sector_id} onChange={(event) => setReportForm({ ...reportForm, sector_id: event.target.value })} required><option value="">Selecciona un sector</option>{sectorList.map((sector) => <option key={sector.id} value={sector.id}>{sector.name}</option>)}</select></label><label>Tipo de problema<input value={reportForm.category} onChange={(event) => setReportForm({ ...reportForm, category: event.target.value })} maxLength="80" placeholder="Ej. Fuga de agua" required /></label><label>Prioridad<select value={reportForm.priority} onChange={(event) => setReportForm({ ...reportForm, priority: event.target.value })}><option value="low">Baja</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select></label><label className="full-width">Descripción<textarea value={reportForm.description} onChange={(event) => setReportForm({ ...reportForm, description: event.target.value })} minLength="20" maxLength="2000" placeholder="Explica qué sucede y dónde ocurre." required /></label><label className="full-width">Evidencias opcionales (JPEG, PNG o PDF; máximo 5 MB cada una)<input type="file" multiple accept="image/jpeg,image/png,application/pdf" onChange={(event) => setFiles(Array.from(event.target.files ?? []))} /></label><button disabled={submittingReport} type="submit">{submittingReport ? 'Enviando…' : 'Enviar reporte'}</button></form></section>
      <section className="catalog"><div className="section-heading"><div><p className="eyebrow">Historial</p><h2>Reportes</h2></div></div><div className="cards">{reportList.map((report) => <article className="card report-card" key={report.id}><span className={`badge ${report.status}`}>{report.status}</span><h3>{report.category}</h3><p>{report.description}</p><small>{report.sector.name} · Prioridad: {report.priority}</small>{report.followups?.length > 0 && <div className="followup-list">{report.followups.map((followup) => <p key={followup.id}><strong>{followup.user.name}:</strong> {followup.message}</p>)}</div>}{canManageReports && <div className="followup-form"><textarea value={followups[report.id]?.message ?? ''} onChange={(event) => setFollowups({ ...followups, [report.id]: { ...followups[report.id], message: event.target.value } })} placeholder="Agregar seguimiento" /><select value={followups[report.id]?.new_status ?? ''} onChange={(event) => setFollowups({ ...followups, [report.id]: { ...followups[report.id], new_status: event.target.value } })}><option value="">Sin cambio de estado</option><option value="in_progress">En proceso</option><option value="resolved">Resuelto</option><option value="closed">Cerrado</option></select><button type="button" onClick={() => handleFollowup(report.id)}>Guardar seguimiento</button></div>}</article>)}</div>{!loading && !reportList.length && <p className="empty">Aún no hay reportes para mostrar.</p>}</section>
      <section className="catalog"><div className="section-heading"><div><p className="eyebrow">Áreas atendidas</p><h2>Sectores activos</h2></div></div><div className="cards">{sectorList.map((sector) => <article className="card" key={sector.id}><h3>{sector.name}</h3><p>{sector.description || 'Sin descripción adicional.'}</p><small>{sector.location_description || 'Ubicación por definir'}</small></article>)}</div></section>
      <section className="catalog"><div className="section-heading"><div><p className="eyebrow">Inventario</p><h2>Recursos</h2></div><label className="filter">Estado<select value={resourceStatus} onChange={(event) => setResourceStatus(event.target.value)}><option value="">Todos</option><option value="available">Disponibles</option><option value="maintenance">En mantenimiento</option><option value="inactive">Inactivos</option></select></label></div><div className="cards">{resourceList.map((resource) => <article className="card" key={resource.id}><span className={`badge ${resource.status}`}>{resource.status === 'available' ? 'Disponible' : resource.status === 'maintenance' ? 'Mantenimiento' : 'Inactivo'}</span><h3>{resource.name}</h3><p>{resource.resource_type} · {resource.location_description || 'Ubicación por definir'}</p><small>{resource.capacity ? `${resource.capacity} ${resource.capacity_unit || ''}` : 'Capacidad no registrada'}</small></article>)}</div>{!loading && !resourceList.length && <p className="empty">No hay recursos con este filtro.</p>}</section>
    </main>
  )
}

export default App
