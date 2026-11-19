# Contrato inicial de API — v1

Base local: `http://localhost:8080/api/v1`

## Convenciones

- Las rutas devuelven JSON y usan códigos HTTP semánticos.
- Validaciones: `422`; no autenticado: `401`; sin permiso: `403`; conflicto de negocio: `409`.
- Las rutas operativas requerirán `auth:sanctum`.
- React utilizará Sanctum en modo SPA con cookies y protección CSRF; Livewire usará la sesión Laravel.

## Endpoints disponibles

| Método | Ruta | Autenticación | Respuesta |
|---|---|---|---|
| `GET` | `/health` | No | `{ "status": "ok" }` |
| `POST` | `/auth/login` | No, con cookie CSRF en SPA | Identidad autenticada y cookie de sesión. |
| `GET` | `/auth/me` | `auth:sanctum` | Identidad de la sesión actual. |
| `POST` | `/auth/logout` | `auth:sanctum` | `204 No Content`. |
| `GET` | `/sectors` | `auth:sanctum` | Sectores con estado `active`, ordenados por nombre. |
| `GET` | `/resources` | `auth:sanctum` | Recursos; admite filtros opcionales `status` y `type`. |
| `GET` | `/shifts` | `auth:sanctum` | Turnos; admite `sector_id` y `status`. |
| `POST` | `/shifts` | Admin/coordinator | Programa un turno y devuelve `409` si existe solapamiento. |
| `PATCH` | `/shifts/{shift}/status` | Admin/coordinator asignado | Cambia estado respetando transiciones válidas. |
| `PUT` | `/shifts/{shift}/result` | Admin/coordinator asignado | Registra el volumen de un turno completado. |
| `GET` | `/reports` | `auth:sanctum` | Listado filtrado por rol y sector. |
| `POST` | `/reports` | `auth:sanctum` | Crea un reporte propio. |
| `POST` | `/reports/{report}/followups` | Admin/coordinator asignado | Agrega seguimiento y cambio de estado opcional. |
| `POST` | `/reports/{report}/evidences` | Autorizado | Sube JPEG, PNG o PDF privado de hasta 5 MB. |

## Flujo de sesión para React y Next.js

1. Desde React (`http://localhost:5175`), solicitar `GET http://localhost:8080/sanctum/csrf-cookie` incluyendo `credentials: 'include'`.
2. Enviar `POST /auth/login` con `username` y `password`, también con `credentials: 'include'`.
3. Consultar `GET /auth/me` para restaurar la sesión al cargar la aplicación.

## Ejemplos de consulta

```text
GET /resources?status=available&type=Tanque
GET /resources?status=maintenance
```

Las colecciones se envuelven en la propiedad `data` y no exponen datos de cuentas ni de administración.
