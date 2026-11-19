"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { z } from "zod";
import { canManageInterruptions, currentUser } from "@/lib/auth";
import { prisma } from "@/lib/prisma";

const interruptionSchema = z.object({
  sectorId: z.coerce.number().int().positive(),
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
    description: formData.get("description") || undefined,
    affectedHouseholds: formData.get("affectedHouseholds") || undefined,
    startedAt: formData.get("startedAt"),
  });
  if (!parsed.success) errorRedirect("Revisa los datos de la interrupción.");

  const allowed = user.role === "admin" || user.sectors.some((sector) => sector.id === parsed.data.sectorId);
  if (!allowed) errorRedirect("No administras el sector seleccionado.");

  await prisma.service_interruptions.create({ data: {
    sector_id: BigInt(parsed.data.sectorId), created_by_user_id: BigInt(user.id),
    cause: parsed.data.cause, description: parsed.data.description || null,
    affected_households: parsed.data.affectedHouseholds ?? null, started_at: parsed.data.startedAt,
  } });
  revalidatePath("/");
  redirect("/?success=Interrupción registrada correctamente.");
}

export async function restoreInterruption(formData: FormData): Promise<void> {
  const user = await managerOrRedirect();
  const id = z.coerce.number().int().positive().safeParse(formData.get("id"));
  if (!id.success) errorRedirect("La interrupción indicada no es válida.");
  const interruption = await prisma.service_interruptions.findUnique({ where: { id: BigInt(id.data) } });
  if (!interruption || interruption.restored_at) errorRedirect("La interrupción ya no está activa.");

  const allowed = user.role === "admin" || user.sectors.some((sector) => BigInt(sector.id) === interruption.sector_id);
  if (!allowed) errorRedirect("No administras el sector de esta interrupción.");
  await prisma.service_interruptions.update({ where: { id: interruption.id }, data: { restored_at: new Date() } });
  revalidatePath("/");
  redirect("/?success=Servicio marcado como restablecido.");
}
