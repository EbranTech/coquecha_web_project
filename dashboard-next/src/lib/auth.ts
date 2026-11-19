import { headers } from "next/headers";

export type SessionUser = {
  id: number;
  username: string;
  name: string;
  role: "admin" | "coordinator" | "neighbor";
  sectors: Array<{ id: number; name: string }>;
};

export async function currentUser(): Promise<SessionUser | null> {
  const cookie = (await headers()).get("cookie");
  if (!cookie) return null;

  const response = await fetch(`${process.env.LARAVEL_INTERNAL_URL ?? "http://localhost:8080"}/api/v1/auth/me`, {
    headers: { Cookie: cookie, Origin: "http://localhost:3000", Referer: "http://localhost:3000/" },
    cache: "no-store",
  });

  if (!response.ok) return null;
  return ((await response.json()) as { user: SessionUser }).user;
}

export function canManageInterruptions(user: SessionUser): boolean {
  return user.role === "admin" || user.role === "coordinator";
}
