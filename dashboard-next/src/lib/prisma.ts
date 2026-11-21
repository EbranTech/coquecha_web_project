import { PrismaMariaDb } from "@prisma/adapter-mariadb";
import { PrismaClient } from "@/generated/prisma/client";
import { existsSync, readFileSync } from "node:fs";

const databaseUrl = new URL(process.env.DATABASE_URL!);
const sslRequested = process.env.DB_SSL === "true"
  || (process.env.DB_SSL !== "false" && databaseUrl.hostname.endsWith(".rds.amazonaws.com"));
const sslCaPath = process.env.DB_SSL_CA_PATH ?? "/etc/ssl/certs/rds-global-bundle.pem";

if (sslRequested && !existsSync(/* turbopackIgnore: true */ sslCaPath)) {
  throw new Error(`DB_SSL está habilitado, pero no existe el certificado CA en ${sslCaPath}.`);
}

const adapter = new PrismaMariaDb({
  host: databaseUrl.hostname,
  port: Number(databaseUrl.port || 3306),
  user: decodeURIComponent(databaseUrl.username),
  password: decodeURIComponent(databaseUrl.password),
  database: databaseUrl.pathname.slice(1),
  connectionLimit: 5,
  connectTimeout: 5_000,
  acquireTimeout: 10_000,
  ...(sslRequested
    ? { ssl: { ca: readFileSync(/* turbopackIgnore: true */ sslCaPath, "utf8"), rejectUnauthorized: true } }
    : {}),
});
const globalForPrisma = globalThis as unknown as { prisma?: PrismaClient };

export const prisma = globalForPrisma.prisma ?? new PrismaClient({ adapter });

if (process.env.NODE_ENV !== "production") globalForPrisma.prisma = prisma;
