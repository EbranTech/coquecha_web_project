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

- [x] Modelar las migraciones de `roles`, `people`, `user_accounts`, `sectors` y `resources`.
- [x] Configurar el modelo de autenticación, roles y datos semilla iniciales.
- [x] Instalar/configurar Sanctum y definir el contrato de API versión 1.

## Fase 2 — Panel administrativo Livewire

- [x] Crear inicio y cierre de sesión web para acceder al panel Laravel.
- [x] Proteger las rutas y acciones administrativas con autenticación y rol `admin`.
- [x] Crear el resumen administrativo con métricas de sectores y recursos.
- [x] Implementar CRUD de sectores mediante Livewire.
- [x] Implementar CRUD de recursos mediante Livewire.
- [x] Añadir Vite de Laravel al entorno Docker (`http://localhost:5174`).

## Fase 3 — API operativa y React

- [x] Exponer sectores activos y recursos autenticados en API v1.
- [x] Añadir filtros de recursos por estado y tipo.
- [x] Reemplazar la plantilla React por inicio de sesión y consulta de catálogos.
- [x] Verificar contrato de API, pruebas aisladas y compilación de React.
- [x] Cerrar la fase con commit y push.

## Próxima acción

Comenzar la Fase 4: modelado y gestión de turnos de distribución.
