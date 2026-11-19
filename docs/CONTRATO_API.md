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
