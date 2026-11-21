"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { z } from "zod";
import { canManageInterruptions, currentUser } from "@/lib/auth";
import { prisma } from "@/lib/prisma";

const interruptionSchema = z.object({
  sectorId: z.coerce.number().int().positive(),
  reportId: z.coerce.number().int().positive().optional(),
  cause: z.string().trim().min(3).max(150),
  description: z.string().trim().max(2000).optional(),
  affectedHouseholds: z.coerce.number().int().min(0).max(1_000_000).optional(),
  startedAt: z.coerce.date(),
});

function errorRedirect(message: string): never {
  redirect(`/?error=${encodeURIComponent(message)}`);
}

async function managerOrRedirect() {
  const user = await currentUser();
  if (!user || !canManageInterruptions(user)) errorRedirect("No tienes permiso para administrar interrupciones.");
  return user;
}

export async function createInterruption(formData: FormData): Promise<void> {
  const user = await managerOrRedirect();
  const parsed = interruptionSchema.safeParse({
    sectorId: formData.get("sectorId"), cause: formData.get("cause"),
    reportId: formData.get("reportId") || undefined,
    description: formData.get("description") || undefined,
    affectedHouseholds: formData.get("affectedHouseholds") || undefined,
    startedAt: formData.get("startedAt"),
  });
  if (!parsed.success) errorRedirect("Revisa los datos de la interrupción.");

  const allowed = user.role === "admin" || user.sectors.some((sector) => sector.id === parsed.data.sectorId);
  if (!allowed) errorRedirect("No administras el sector seleccionado.");

  await prisma.$transaction(async (transaction) => {
    const reportId = parsed.data.reportId ? BigInt(parsed.data.reportId) : null;

    if (reportId) {
      const updated = await transaction.reports.updateMany({
        where: { id: reportId, sector_id: BigInt(parsed.data.sectorId), status: { in: ["pending", "in_progress"] } },
        data: { status: "in_progress", resolved_at: null },
      });
      if (updated.count !== 1) {
        errorRedirect("Elige un reporte abierto del sector seleccionado o registra la interrupción sin asociar uno.");
      }
    }

    await transaction.service_interruptions.create({ data: {
      sector_id: BigInt(parsed.data.sectorId), created_by_user_id: BigInt(user.id),
      report_id: reportId,
      cause: parsed.data.cause, description: parsed.data.description || null,
      affected_households: parsed.data.affectedHouseholds ?? null, started_at: parsed.data.startedAt,
    } });
  });
  revalidatePath("/");
  redirect("/?success=Interrupción registrada correctamente.");
}

export async function restoreInterruption(formData: FormData): Promise<void> {
  const user = await managerOrRedirect();
  const id = z.coerce.number().int().positive().safeParse(formData.get("id"));
  if (!id.success) errorRedirect("La interrupción indicada no es válida.");
  await prisma.$transaction(async (transaction) => {
    const interruption = await transaction.service_interruptions.findUnique({ where: { id: BigInt(id.data) } });
    if (!interruption || interruption.restored_at) errorRedirect("La interrupción ya no está activa.");

    const allowed = user.role === "admin" || user.sectors.some((sector) => BigInt(sector.id) === interruption.sector_id);
    if (!allowed) errorRedirect("No administras el sector de esta interrupción.");

    const restoredAt = new Date();
    const updated = await transaction.service_interruptions.updateMany({
      where: { id: interruption.id, restored_at: null },
      data: { restored_at: restoredAt },
    });
    if (updated.count !== 1) errorRedirect("La interrupción ya no está activa.");

    if (interruption.report_id !== null) {
      const otherActiveInterruptions = await transaction.service_interruptions.count({
        where: { report_id: interruption.report_id, restored_at: null },
      });
      if (otherActiveInterruptions === 0) {
        await transaction.reports.updateMany({
          where: { id: interruption.report_id, status: "in_progress" },
          data: { status: "resolved", resolved_at: restoredAt },
        });
      }
    }
  });
  revalidatePath("/");
  redirect("/?success=Servicio restablecido; el reporte asociado quedó resuelto.");
}
