import { useEffect, useState } from 'react'
import { currentUser, login, logout, resources, sectors } from './api'
import './App.css'

function App() {
  const [user, setUser] = useState(null)
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [resourceStatus, setResourceStatus] = useState('')
  const [sectorList, setSectorList] = useState([])
  const [resourceList, setResourceList] = useState([])

  useEffect(() => {
    currentUser().then(({ user: activeUser }) => setUser(activeUser)).catch(() => setUser(null)).finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    if (!user) return
    setLoading(true)
    Promise.all([sectors(), resources({ status: resourceStatus })])
      .then(([sectorResponse, resourceResponse]) => { setSectorList(sectorResponse.data); setResourceList(resourceResponse.data) })
      .catch((requestError) => setError(requestError.message))
      .finally(() => setLoading(false))
  }, [user, resourceStatus])

  async function handleLogin(event) {
    event.preventDefault()
    setLoading(true); setError('')
    try { const response = await login(username, password); setUser(response.user); setPassword('') } catch (requestError) { setError(requestError.message) } finally { setLoading(false) }
  }

  async function handleLogout() {
    await logout(); setUser(null); setSectorList([]); setResourceList([]); setResourceStatus('')
  }

  if (loading && !user) return <main className="page"><p>Cargando plataforma…</p></main>

  if (!user) return <main className="page login-page"><section className="login-card"><p className="eyebrow">Coquechas</p><h1>Consulta comunitaria</h1><p className="intro">Consulta los sectores y recursos operativos de la comunidad.</p><form onSubmit={handleLogin}><label>Usuario<input value={username} onChange={(event) => setUsername(event.target.value)} required /></label><label>Contraseña<input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required /></label>{error && <p className="error">{error}</p>}<button disabled={loading} type="submit">{loading ? 'Ingresando…' : 'Ingresar'}</button></form></section></main>

  return (
    <main className="page"><header className="topbar"><div><p className="eyebrow">Coquechas</p><h1>Recursos comunitarios</h1></div><div className="user-actions"><span>{user.name} · {user.role}</span><button className="secondary" onClick={handleLogout} type="button">Salir</button></div></header>{error && <p className="error">{error}</p>}<section className="summary"><article><strong>{sectorList.length}</strong><span>Sectores activos</span></article><article><strong>{resourceList.length}</strong><span>Recursos mostrados</span></article></section><section className="catalog"><div className="section-heading"><div><p className="eyebrow">Áreas atendidas</p><h2>Sectores activos</h2></div></div><div className="cards">{sectorList.map((sector) => <article className="card" key={sector.id}><h3>{sector.name}</h3><p>{sector.description || 'Sin descripción adicional.'}</p><small>{sector.location_description || 'Ubicación por definir'}</small></article>)}</div></section><section className="catalog"><div className="section-heading"><div><p className="eyebrow">Inventario</p><h2>Recursos</h2></div><label className="filter">Estado<select value={resourceStatus} onChange={(event) => setResourceStatus(event.target.value)}><option value="">Todos</option><option value="available">Disponibles</option><option value="maintenance">En mantenimiento</option><option value="inactive">Inactivos</option></select></label></div><div className="cards">{resourceList.map((resource) => <article className="card" key={resource.id}><span className={`badge ${resource.status}`}>{resource.status === 'available' ? 'Disponible' : resource.status === 'maintenance' ? 'Mantenimiento' : 'Inactivo'}</span><h3>{resource.name}</h3><p>{resource.resource_type} · {resource.location_description || 'Ubicación por definir'}</p><small>{resource.capacity ? `${resource.capacity} ${resource.capacity_unit || ''}` : 'Capacidad no registrada'}</small></article>)}</div>{!loading && !resourceList.length && <p className="empty">No hay recursos con este filtro.</p>}</section></main>
  )
}

export default App
