# Coquechas Agua

Plataforma comunitaria para administrar la distribución de agua, programar turnos sin conflictos, registrar reportes con evidencias e identificar interrupciones mediante indicadores reales.

## Arquitecturas

- `backend-laravel`: Laravel + Livewire y API REST con Sanctum.
- `frontend-react`: React + Vite para operaciones de turnos y reportes.
- `dashboard-next`: Next.js + Prisma para interrupciones e indicadores.

Las tres aplicaciones comparten la base MariaDB `coquechas_agua`. Docker Compose será el único método documentado para ejecutar el entorno local y los contenedores de aplicación se desplegarán en EC2.

## Estado

La Fase 0 está en preparación. El avance y los siguientes pasos están documentados en [docs/PROGRESO.md](docs/PROGRESO.md).

El plan completo de la solución se conserva en el directorio de trabajo padre como `PLAN_IMPLEMENTACION_COQUECHAS.md`; será incorporado a `docs/` antes de iniciar la implementación funcional.
