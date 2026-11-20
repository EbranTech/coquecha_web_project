# Auditoría QA local previa a AWS

Fecha: 2026-11-19. Entorno: Docker Compose local, MariaDB y Moto Server.

## Estado comprobado

| Área | Resultado | Evidencia |
|---|---|---|
| Contenedores | Correcto | MariaDB saludable; Laravel, React, Next y Moto en ejecución. |
| Laravel | Correcto | `php artisan test`: 27 pruebas y 63 aserciones aprobadas. |
| React | Correcto | `npm run build` aprobado. |
| Next | Correcto | `npm run build` y comprobación manual del panel aprobados. |
| Datos demo | Correcto | Seeder idempotente crea `admin`, `coordinador`, `vecino`, 3 sectores y 2 recursos. |
| S3 local | Correcto | Moto y la prueba de evidencia PDF privada están cubiertos por Feature tests. |

> Para ejecutar pruebas sin alterar los datos de demostración en MariaDB, usar SQLite en memoria de forma explícita: `docker compose -f infrastructure/docker-compose.yml exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= laravel-php php artisan test`.

## Matriz funcional

| Rol | Funcionalidad | Estado |
|---|---|---|
| Admin | Inicio/cierre de sesión y API de identidad | Cubierto por pruebas y recorrido manual previo. |
| Admin | CRUD Livewire de sectores y recursos | Crear y asignar coordinador cubiertos; edición/eliminación requieren recorrido manual final. La eliminación con historial ahora se bloquea de forma explícita. |
| Admin | Turnos, cambios de estado y volumen distribuido | Cubierto por API; el volumen solo se registra tras completar el turno. |
| Coordinator | Crear turno en sector asignado | Cubierto por Feature test. |
| Coordinator | Intentar crear turno fuera de su sector | Rechazado con `403`, cubierto por Feature test. |
| Neighbor | Crear y consultar solo sus reportes | Cubierto por Feature test. |
| Coordinator/Admin | Seguimiento y cambio de estado de reportes | Cubierto por Feature test; sector no asignado es rechazado. |
| Usuario autorizado | Evidencia PDF privada | Cubierto por Feature test; tipo no permitido se rechaza. |
| Admin/Coordinator | Crear y restablecer interrupciones | Recorrido manual comprobado con Next; la fila de prueba fue eliminada. |
| Admin/Coordinator | Indicadores Next | Implementados desde MariaDB: turnos del día, sectores atendidos, reportes pendientes, interrupciones, volumen y hogares afectados. |

## Correcciones aplicadas durante QA

1. Se añadió `shift_results` y el endpoint `PUT /api/v1/shifts/{shift}/result` para registrar volumen real al completar un turno.
2. Se ampliaron los indicadores de Next y se añadieron historial reciente de interrupciones y reportes de prioridad alta/urgente.
3. Se restringieron los privilegios de Prisma: escritura solo en interrupciones y lectura solo de las tablas necesarias para indicadores.
4. El seeder ahora prepara catálogos y asignación de coordinador, no solo cuentas.
5. Eliminar sectores o recursos con historial ya no provoca un error de clave foránea: se informa que deben inactivarse.

## Pendiente antes de AWS

- La edición completa de un turno (fecha, recursos y notas) aún no está expuesta en la API/React; el MVP permite crear y cancelar/cambiar estado.
- Faltan pruebas automatizadas de componentes React y de Server Actions de Next; actualmente se validan con builds, Feature tests Laravel y recorridos manuales.
- El panel Next muestra indicadores del día; faltan controles visuales de rango de fechas y paginación del historial.
- La interrupción permite relación opcional con reporte a nivel de base, pero el selector aún no está presentado en el formulario Next.
- Falta un recorrido manual documentado de edición y eliminación de sectores/recursos, y de las pantallas React con los tres roles una vez superado el límite temporal de intentos de login local.
- Falta el Compose de producción, imágenes productivas, `NEXT /api/health`, configuración de dominios/HTTPS y toda la fase AWS. No debe iniciarse hasta cerrar estos pendientes.
