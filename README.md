# Coquechas Agua

Plataforma comunitaria para administrar la distribución de agua, programar turnos sin conflictos, registrar reportes con evidencias e identificar interrupciones mediante indicadores reales.

## Arquitecturas

- `backend-laravel`: Laravel + Livewire y API REST con Sanctum.
- `frontend-react`: React + Vite para operaciones de turnos y reportes.
- `dashboard-next`: Next.js + Prisma para interrupciones e indicadores.

Las tres aplicaciones comparten la base MariaDB `coquechas_agua`. Docker Compose será el único método documentado para ejecutar el entorno local y los contenedores de aplicación se desplegarán en EC2.

## Ejecución local

Desde la raíz del repositorio:

```powershell
docker compose -f infrastructure/docker-compose.yml up -d
```

El panel Laravel está en `http://localhost:8080/login`. Para desarrollo, Vite de Laravel usa `http://localhost:5174`, React usa `5175` y Next.js usa `3000`.

Cuenta demo de administración: `admin` / `password`.

## Estado

Las Fases 0, 1 y 2 están completadas. El avance y los siguientes pasos están documentados en [docs/PROGRESO.md](docs/PROGRESO.md).

La arquitectura objetivo, la creación de cada recurso en la consola AWS, las configuraciones de seguridad y el procedimiento de actualización están documentados en [docs/DESPLIEGUE_AWS.md](docs/DESPLIEGUE_AWS.md).

El uso de cada módulo, los roles y el recorrido completo de QA están explicados en [docs/MANUAL_FUNCIONAL.md](docs/MANUAL_FUNCIONAL.md).

La explicación técnica integral del código, módulos, API, base de datos, Docker y comandos está en [docs/GUIA_TECNICA_COMPLETA.md](docs/GUIA_TECNICA_COMPLETA.md).

El plan completo de la solución se conserva en el directorio de trabajo padre como `PLAN_IMPLEMENTACION_COQUECHAS.md`; será incorporado a `docs/` antes de iniciar la implementación funcional.
