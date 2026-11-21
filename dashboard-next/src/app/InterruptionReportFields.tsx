"use client";

import { useState } from "react";

type SectorOption = { id: string; name: string };
type ReportOption = {
  id: string;
  sectorId: string;
  category: string;
  priority: string;
  description: string;
  createdAt: string | null;
};

const priorityLabels: Record<string, string> = {
  low: "baja",
  normal: "normal",
  high: "alta",
  urgent: "urgente",
};

const dateFormatter = new Intl.DateTimeFormat("es-GT", { dateStyle: "medium" });

export default function InterruptionReportFields({ sectors, reports }: { sectors: SectorOption[]; reports: ReportOption[] }) {
  const [sectorId, setSectorId] = useState("");
  const [reportId, setReportId] = useState("");
  const sectorReports = reports.filter((report) => report.sectorId === sectorId);
  const selectedReport = sectorReports.find((report) => report.id === reportId);

  return <>
    <label>Sector<select name="sectorId" required value={sectorId} onChange={(event) => { setSectorId(event.target.value); setReportId(""); }}>
      <option value="" disabled>Selecciona un sector</option>
      {sectors.map((sector) => <option key={sector.id} value={sector.id}>{sector.name}</option>)}
    </select></label>
    <label>Reporte relacionado (opcional)<select name="reportId" value={reportId} disabled={!sectorId || sectorReports.length === 0} onChange={(event) => setReportId(event.target.value)}>
      <option value="" disabled={!sectorId}>{sectorId ? "Sin reporte relacionado" : "Selecciona primero un sector"}</option>
      {sectorReports.map((report) => <option key={report.id} value={report.id}>#{report.id} · {report.category} · prioridad {priorityLabels[report.priority] ?? report.priority}{report.createdAt ? ` · ${dateFormatter.format(new Date(report.createdAt))}` : ""}</option>)}
    </select>
    {!sectorId ? <small className="field-hint">Selecciona un sector para ver sus reportes abiertos.</small> : sectorReports.length === 0 ? <small className="field-hint">No hay reportes abiertos en este sector; puedes registrar la interrupción sin asociar uno.</small> : selectedReport ? <small className="field-hint">{selectedReport.description}</small> : <small className="field-hint">Solo aparecen reportes pendientes o en curso de {sectors.find((sector) => sector.id === sectorId)?.name}.</small>}
    </label>
  </>;
}
