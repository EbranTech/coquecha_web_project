import Form from "next/form";
import Link from "next/link";
import { createInterruption, restoreInterruption } from "./actions";
import { canManageInterruptions, currentUser } from "@/lib/auth";
import { prisma } from "@/lib/prisma";
import { adminUrl, loginUrl, operationsUrl } from "@/lib/urls";

type HomeProps = { searchParams: Promise<{ error?: string; success?: string; from?: string; to?: string; page?: string }> };
const dateFormatter = new Intl.DateTimeFormat("es-GT", { dateStyle: "medium", timeStyle: "short" });
const historyPageSize = 10;

function dateInputValue(value: Date): string {
  return value.toISOString().slice(0, 10);
}

function parseDate(value: string | undefined, fallback: Date): Date {
  if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return fallback;
  const date = new Date(`${value}T00:00:00`);
  return Number.isNaN(date.getTime()) ? fallback : date;
}

export default async function Home({ searchParams }: HomeProps) {
  const [user, params] = await Promise.all([currentUser(), searchParams]);
  if (!user || !canManageInterruptions(user)) {
    return <main className="access-message"><p className="eyebrow">COQUECHAS · OPERACIONES</p><h1>Panel de interrupciones</h1><p>Inicia sesión con una cuenta autorizada para acceder.</p><a href={loginUrl}>Ir al acceso central</a></main>;
  }

  const sectors = user.role === "admin"
    ? await prisma.sectors.findMany({ where: { status: "active" }, orderBy: { name: "asc" } })
    : user.sectors.map((sector) => ({ ...sector, id: BigInt(sector.id) }));
  const sectorIds = sectors.map((sector) => sector.id);
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate() + 1);
  const sectorFilter = user.role === "admin" ? {} : { sector_id: { in: sectorIds } };
  const historyFrom = parseDate(params.from, today);
  const historyToStart = parseDate(params.to, today);
  const historyTo = new Date(historyToStart); historyTo.setDate(historyTo.getDate() + 1);
  const historyPage = Math.max(1, Number.parseInt(params.page ?? "1", 10) || 1);
  const historyFilter = { ...sectorFilter, started_at: { gte: historyFrom, lt: historyTo } };

  const [activeInterruptions, shiftsToday, completedShifts, pendingReports, priorityReports, recentInterruptions, historyCount, availableReports, volumes] = await Promise.all([
    prisma.service_interruptions.findMany({ where: { ...sectorFilter, restored_at: null }, include: { sectors: true }, orderBy: { started_at: "desc" } }),
    prisma.shifts.count({ where: { ...sectorFilter, status: { in: ["planned", "in_progress"] }, starts_at: { gte: today, lt: tomorrow } } }),
    prisma.shifts.findMany({ where: { ...sectorFilter, status: "completed", starts_at: { gte: today, lt: tomorrow } }, select: { sector_id: true } }),
    prisma.reports.count({ where: { ...sectorFilter, status: { in: ["pending", "in_progress"] } } }),
    prisma.reports.findMany({ where: { ...sectorFilter, status: { in: ["pending", "in_progress"] }, priority: { in: ["high", "urgent"] } }, orderBy: [{ priority: "desc" }, { created_at: "desc" }], take: 5 }),
    prisma.service_interruptions.findMany({ where: historyFilter, include: { sectors: true }, orderBy: { started_at: "desc" }, skip: (historyPage - 1) * historyPageSize, take: historyPageSize }),
    prisma.service_interruptions.count({ where: historyFilter }),
    prisma.reports.findMany({ where: { ...sectorFilter, status: { in: ["pending", "in_progress"] } }, select: { id: true, sector_id: true, category: true, priority: true }, orderBy: { created_at: "desc" }, take: 100 }),
    prisma.shift_results.findMany({ where: { shift: { ...sectorFilter, status: "completed", starts_at: { gte: today, lt: tomorrow } } }, select: { delivered_volume: true, volume_unit: true } }),
  ]);
  const deliveredVolume = volumes.reduce((total, result) => total + Number(result.delivered_volume), 0);
  const affectedHouseholds = activeInterruptions.reduce((total, item) => total + (item.affected_households ?? 0), 0);
  const totalHistoryPages = Math.max(1, Math.ceil(historyCount / historyPageSize));
  const historyQuery = new URLSearchParams({ from: dateInputValue(historyFrom), to: dateInputValue(historyToStart) });

  return <main className="dashboard">
    <header className="hero"><div><p className="eyebrow">COQUECHAS · OPERACIONES</p><h1>Interrupciones e indicadores</h1><p>Indicadores operativos calculados con los datos reales de hoy.</p></div><div className="user"><p className="module-label">Volver a</p><nav aria-label="Módulos de retorno" className="module-nav"><a href={operationsUrl}>← Operaciones</a>{user.role === "admin" && <a href={adminUrl}>⚙ Administración</a>}</nav><p>{user.name} · {user.role === "admin" ? "Administrador" : "Coordinador"}</p></div></header>
    {params.error && <p className="notice error" role="alert">{params.error}</p>}{params.success && <p className="notice success">{params.success}</p>}
    <section className="metrics" aria-label="Indicadores de hoy"><article><strong>{shiftsToday}</strong><span>Turnos de hoy</span></article><article><strong>{new Set(completedShifts.map((shift) => shift.sector_id.toString())).size}</strong><span>Sectores atendidos hoy</span></article><article><strong>{pendingReports}</strong><span>Reportes pendientes</span></article><article><strong>{activeInterruptions.length}</strong><span>Interrupciones activas</span></article><article><strong>{deliveredVolume.toLocaleString("es-GT")}</strong><span>Volumen distribuido hoy</span></article><article><strong>{affectedHouseholds}</strong><span>Hogares afectados</span></article></section>
    <section className="grid"><form action={createInterruption} className="card form-card"><p className="eyebrow">NUEVO AVISO</p><h2>Registrar interrupción</h2><label>Sector<select name="sectorId" required defaultValue=""><option value="" disabled>Selecciona un sector</option>{sectors.map((sector) => <option key={sector.id.toString()} value={sector.id.toString()}>{sector.name}</option>)}</select></label><label>Reporte relacionado (opcional)<select name="reportId" defaultValue=""><option value="">Sin reporte relacionado</option>{availableReports.map((report) => <option key={report.id.toString()} value={report.id.toString()}>{report.category} · prioridad {report.priority}</option>)}</select></label><label>Causa<input name="cause" required minLength={3} maxLength={150} placeholder="Ej. reparación de tubería" /></label><label>Inicio<input name="startedAt" type="datetime-local" required /></label><label>Hogares afectados (opcional)<input name="affectedHouseholds" type="number" min="0" /></label><label>Descripción (opcional)<textarea name="description" maxLength={2000} rows={4} /></label><button type="submit">Registrar interrupción</button></form><section className="card list-card"><p className="eyebrow">SEGUIMIENTO</p><h2>Interrupciones activas</h2>{activeInterruptions.length === 0 ? <p className="empty">No hay interrupciones activas en tus sectores.</p> : <div className="interruption-list">{activeInterruptions.map((item) => <article className="interruption" key={item.id.toString()}><div><h3>{item.sectors.name}</h3><p>{item.cause}</p><small>Desde {dateFormatter.format(item.started_at)}{item.affected_households !== null && ` · ${item.affected_households} hogares`}</small>{item.description && <p className="description">{item.description}</p>}</div><form action={restoreInterruption}><input type="hidden" name="id" value={item.id.toString()} /><button className="secondary" type="submit">Restablecer</button></form></article>)}</div>}</section></section>
    <section className="grid history"><section className="card list-card"><p className="eyebrow">HISTORIAL</p><h2>Interrupciones</h2><Form action="/" scroll={false} className="history-filter"><label>Desde<input name="from" type="date" defaultValue={dateInputValue(historyFrom)} /></label><label>Hasta<input name="to" type="date" defaultValue={dateInputValue(historyToStart)} /></label><button type="submit">Filtrar</button></Form>{recentInterruptions.length === 0 ? <p className="empty">Sin interrupciones en este periodo.</p> : recentInterruptions.map((item) => <article className="interruption" key={item.id.toString()}><div><h3>{item.sectors.name}</h3><p>{item.cause}</p><small>{item.restored_at ? `Restablecida el ${dateFormatter.format(item.restored_at)}` : "Activa"}</small></div></article>)}{historyCount > historyPageSize && <nav className="pagination" aria-label="Paginación del historial">{historyPage > 1 && <Link scroll={false} href={`/?${historyQuery.toString()}&page=${historyPage - 1}`}>Anterior</Link>}<span>Página {historyPage} de {totalHistoryPages}</span>{historyPage < totalHistoryPages && <Link scroll={false} href={`/?${historyQuery.toString()}&page=${historyPage + 1}`}>Siguiente</Link>}</nav>}</section><section className="card list-card"><p className="eyebrow">ATENCIÓN PRIORITARIA</p><h2>Reportes urgentes</h2>{priorityReports.length === 0 ? <p className="empty">No hay reportes urgentes o de alta prioridad.</p> : priorityReports.map((report) => <article className="interruption" key={report.id.toString()}><div><h3>{report.category}</h3><p>{report.description}</p><small>Prioridad: {report.priority} · Estado: {report.status}</small></div></article>)}</section></section>
  </main>;
}
