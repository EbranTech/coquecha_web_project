# Auditoría QA local previa a AWS

Fecha: 2026-11-20. Entorno: Docker Compose local, MariaDB y Moto Server.

## Estado comprobado

| Área | Resultado | Evidencia |
|---|---|---|
| Contenedores | Correcto | MariaDB saludable; Laravel, React, Next y Moto en ejecución. |
| Laravel | Correcto | `php artisan test`: 35 pruebas y 84 aserciones aprobadas en SQLite en memoria. |
| React | Correcto | Vitest (1 prueba), `npm run build` y linter aprobados. |
| Next | Correcto | `npm run build`, linter y `GET /api/health` con respuesta `200` aprobados. |
| Datos demo | Correcto | Seeder idempotente crea `admin`, `coordinador`, `vecino`, 3 sectores y 2 recursos. |
| S3 local | Correcto | Moto y la prueba de evidencia PDF privada están cubiertos por Feature tests. |

> Para ejecutar pruebas sin alterar los datos de demostración en MariaDB, usar SQLite en memoria de forma explícita: `docker compose -f infrastructure/docker-compose.yml exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= laravel-php php artisan test`.

## Matriz funcional

| Rol | Funcionalidad | Estado |
|---|---|---|
| Admin | Inicio/cierre de sesión y API de identidad | Cubierto por pruebas y recorrido manual previo. |
| Admin | CRUD Livewire de sectores, recursos y usuarios | Crear, editar, eliminar registros sin historial y desactivar usuarios cubiertos. La eliminación con historial se bloquea de forma explícita. |
| Admin | Turnos, edición, cambios de estado y volumen distribuido | Cubierto por API; la edición conserva controles de sector y conflictos. El volumen solo se registra tras completar el turno. |
| Coordinator | Crear turno en sector asignado | Cubierto por Feature test. |
| Coordinator | Intentar crear turno fuera de su sector | Rechazado con `403`, cubierto por Feature test. |
| Neighbor | Crear y consultar solo sus reportes | Cubierto por Feature test. |
| Coordinator/Admin | Seguimiento y cambio de estado de reportes | Cubierto por Feature test; sector no asignado es rechazado. |
| Usuario autorizado | Evidencia PDF privada | Cubierto por Feature test; tipo no permitido se rechaza. |
| Admin/Coordinator | Crear y restablecer interrupciones | Formulario incluye reporte relacionado opcional y valida que corresponda al mismo sector. |
| Admin/Coordinator | Indicadores Next | Implementados desde MariaDB: turnos del día, sectores atendidos, reportes pendientes, interrupciones, volumen y hogares afectados; historial con rango y paginación. |

## Correcciones aplicadas durante QA

1. Se añadió `shift_results` y el endpoint `PUT /api/v1/shifts/{shift}/result` para registrar volumen real al completar un turno.
2. Se ampliaron los indicadores de Next y se añadieron historial reciente de interrupciones y reportes de prioridad alta/urgente.
3. Se restringieron los privilegios de Prisma: escritura solo en interrupciones y lectura solo de las tablas necesarias para indicadores.
4. El seeder ahora prepara catálogos y asignación de coordinador, no solo cuentas.
5. Eliminar sectores o recursos con historial ya no provoca un error de clave foránea: se informa que deben inactivarse.

## Pendiente antes de AWS

- Realizar la aceptación visual final con usuarios reales en los tres roles; la autorización y los flujos críticos ya están cubiertos de forma automatizada.
- Preparar Compose e imágenes de producción, dominios/HTTPS y ejecutar el aprovisionamiento AWS descrito en `DESPLIEGUE_AWS.md`.
