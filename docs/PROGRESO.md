# Progreso de implementación

## Fase 0 — Preparación

- [x] Crear el repositorio independiente `coquechas-agua`.
- [x] Crear las carpetas compartidas `database/`, `docs/` e `infrastructure/`.
- [x] Definir `.gitignore` y README base.
- [x] Generar `frontend-react` con React + Vite.
- [x] Generar `dashboard-next` con Next.js, TypeScript y App Router.
- [x] Generar `backend-laravel` con Laravel 13 + Livewire.
- [x] Añadir Dockerfiles y Docker Compose de desarrollo.
- [x] Configurar MariaDB y Moto Server como servicios locales. Moto emula endpoints S3 sin requerir una licencia de desarrollo.
- [x] Verificar Laravel (`8080`), React (`5173`), Next.js (`3000`), MariaDB y Moto Server (`5000`).

## Fase 1 — Fundamentos de datos y acceso

- [ ] Modelar las migraciones de `roles`, `people`, `user_accounts`, `sectors` y `resources`.
- [ ] Configurar el modelo de autenticación, roles y datos semilla iniciales.
- [ ] Instalar/configurar Sanctum y definir el contrato de API versión 1.

## Próxima acción

Cerrar la Fase 0 con commit y push. Después, comenzar las migraciones y la autenticación de la Fase 1.
