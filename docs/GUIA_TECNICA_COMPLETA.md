# Guía técnica completa — Coquechas Agua

Esta guía explica el proyecto desde su estructura física hasta el comportamiento de una solicitud, la base de datos y los comandos de operación. Está escrita para poder defender decisiones técnicas frente a un docente, compañero o revisor.

> Alcance: el contenido describe el estado local actual. Los puertos `8080`, `5175`, `3000` y `5000` existen para desarrollo con Docker; en producción se sustituyen por un dominio, HTTPS y la infraestructura definida en `DESPLIEGUE_AWS.md`.

## 1. Qué problema resuelve

Coquechas Agua administra una red comunitaria de distribución de agua. El sistema permite:

- administrar sectores, recursos y cuentas;
- programar turnos sin reservar un mismo recurso en horarios solapados;
- registrar volumen realmente distribuido;
- reportar problemas, darles seguimiento y adjuntar evidencias privadas;
- registrar interrupciones del servicio y restablecimientos;
- ver indicadores operativos del día e historial filtrable.

La solución no es una aplicación monolítica de una sola pantalla: son **tres módulos web especializados** que comparten reglas, identidad y datos.

| Módulo | Tecnología | Puerto local | Responsabilidad |
|---|---:|---:|---|
| Administración/API | Laravel + Livewire + Sanctum | `8080` | Login único, cuentas, catálogos, API y autorización. |
| Operaciones | React + Vite | `5175` | Turnos, inventario visible, reportes y evidencias. |
| Indicadores | Next.js + Prisma | `3000` | Interrupciones, restablecimientos e indicadores. |

## 2. Arquitectura general

```text
Navegador
  ├─ http://localhost:8080/login  ── Laravel: login y administración Livewire
  ├─ http://localhost:5175        ── React: operaciones
  └─ http://localhost:3000        ── Next: indicadores
                                      │
                  Cookie de sesión compartida por localhost
                                      │
               Laravel /api/v1/auth/me (Sanctum)
                                      │
                   MariaDB: coquechas_agua
                         ├─ Laravel/Eloquent escribe y lee
                         └─ Next/Prisma escribe interrupciones y lee indicadores

Evidencias: Laravel → Flysystem S3 → Moto en local / S3 privado en AWS
```

### Por qué hay tres módulos

No son tres sistemas aislados. Separar la interfaz permite que cada responsabilidad use la herramienta más conveniente:

- **Laravel** concentra seguridad, sesiones, validación y administración. Evita duplicar usuarios y contraseñas.
- **Livewire** da interactividad a las pantallas administrativas sin construir una API JavaScript adicional para cada CRUD.
- **React** entrega una interfaz cliente ágil para operaciones cotidianas con datos consumidos desde la API REST.
- **Next.js** ejecuta consultas de indicadores en el servidor y Server Actions para interrupciones, por lo que las credenciales de BD nunca llegan al navegador.

La integridad no depende de que la interfaz “se comporte bien”: Laravel valida los permisos y la base de datos tiene llaves foráneas, índices y restricciones.

## 3. Inicio de sesión único y comunicación entre módulos

El único formulario de credenciales está en `http://localhost:8080/login`.

1. La persona envía usuario y contraseña a Laravel.
2. `AuthenticatedSessionController` usa `Auth::attempt`, verifica que `is_active` sea verdadero y regenera la sesión para prevenir fijación de sesión.
3. Si el rol es `admin`, Laravel abre `/admin`; coordinador y vecino van a Operaciones (`5175`).
4. React y Next consultan `GET /api/v1/auth/me` con la cookie de sesión. Por eso reconocen a la misma persona sin pedir otra contraseña.
5. Si React no tiene una sesión válida, redirige al login central. Next muestra el enlace al login central.
6. Al cerrar sesión desde Laravel o React se invalida la sesión y se vuelve al login de `8080`.

`SANCTUM_STATEFUL_DOMAINS=localhost:5175,localhost:3000` permite que Sanctum trate esos orígenes locales como SPA de confianza. Las cookies se comparten por **host** (`localhost`), no por puerto; por eso `localhost:8080`, `localhost:5175` y `localhost:3000` pueden enviar la misma cookie cuando el navegador usa `credentials: 'include'`.

## 4. Directorios y archivos relevantes

```text
coquechas-agua/
├─ backend-laravel/                 # aplicación Laravel
│  ├─ app/
│  │  ├─ Http/Controllers/          # controladores web y REST
│  │  ├─ Http/Requests/             # reglas de validación de solicitudes
│  │  ├─ Http/Resources/            # forma JSON de respuestas API
│  │  ├─ Models/                    # modelos Eloquent y relaciones
│  │  └─ Services/                  # lógica de dominio reutilizable
│  ├─ bootstrap/app.php             # middleware, manejo de excepciones y rutas
│  ├─ database/migrations/          # evolución versionada de la base
│  ├─ database/seeders/             # datos iniciales y de simulación
│  ├─ resources/views/              # Blade y componentes Livewire
│  ├─ routes/api.php                # API REST /api/v1
│  ├─ routes/web.php                # login y panel administrativo
│  └─ tests/                        # pruebas PHPUnit/Feature
├─ frontend-react/                  # módulo de operaciones
│  ├─ src/App.jsx                   # interfaz y estados principales
│  ├─ src/api.js                    # cliente HTTP hacia Laravel
│  ├─ src/App.css                   # estilos del módulo
│  └─ vite.config.js                # Vite y Vitest
├─ dashboard-next/                  # módulo de indicadores
│  ├─ src/app/page.tsx              # página del dashboard
│  ├─ src/app/actions.ts            # Server Actions de interrupciones
│  ├─ src/app/api/health/route.ts   # health check de Next
│  ├─ src/lib/auth.ts               # lectura de sesión Laravel
│  ├─ src/lib/prisma.ts             # cliente Prisma con MariaDB
│  └─ prisma/schema.prisma          # esquema de Prisma, reflejo de la BD
├─ infrastructure/
│  ├─ docker-compose.yml            # servicios locales
│  └─ nginx/laravel.conf            # Nginx frente a PHP-FPM
└─ docs/                            # manuales, API, QA y AWS
```

## 5. Docker: qué arranca y por qué

El archivo `infrastructure/docker-compose.yml` declara servicios que se comunican en la red privada `coquechas-network`.

| Servicio | Imagen/contenedor | Función |
|---|---|---|
| `mariadb` | MariaDB 11 | Base de datos persistente en el volumen `mariadb_data`. |
| `moto` | Moto Server | Emulador local compatible con S3. No es producción. |
| `laravel-php` | PHP-FPM | Ejecuta PHP y Laravel. |
| `laravel-nginx` | Nginx | Recibe `8080` y envía PHP a `laravel-php:9000`. |
| `laravel-vite` | Node | Compila y sirve los assets de Blade/Livewire en `5174`. |
| `react` | Vite | Sirve Operaciones en host `5175`. |
| `next` | Next dev | Sirve Indicadores en `3000`; consulta MariaDB por red Docker. |
| `mailpit` | opcional | Captura correo local; se activa con perfil `mail`. |

### Comandos cotidianos

Ejecutar desde la raíz del repositorio (`coquechas-agua`):

```powershell
# Crear/iniciar todos los contenedores en segundo plano.
docker compose -f infrastructure/docker-compose.yml up -d

# Ver estado y puertos.
docker compose -f infrastructure/docker-compose.yml ps

# Ver logs de un módulo en tiempo real.
docker compose -f infrastructure/docker-compose.yml logs -f react
docker compose -f infrastructure/docker-compose.yml logs -f next
docker compose -f infrastructure/docker-compose.yml logs -f laravel-php

# Reiniciar solo un módulo después de un problema local.
docker compose -f infrastructure/docker-compose.yml restart next

# Detener contenedores conservando el volumen de la BD.
docker compose -f infrastructure/docker-compose.yml down
```

No usar `down -v` salvo que se quiera destruir la base local, porque elimina los volúmenes.

## 6. Laravel: servidor, reglas y administración

### 6.1 Qué es Laravel en este proyecto

Laravel es el backend PHP. Recibe solicitudes HTTP, autentica, valida datos, coordina transacciones, aplica autorización y responde HTML o JSON. Trabaja con Eloquent, el ORM que convierte filas de la base de datos en objetos PHP.

La ruta web se define en `backend-laravel/routes/web.php`:

- `/` redirige a `/login`.
- `/login` muestra/procesa el único acceso.
- `/logout` requiere una sesión.
- `/admin/*` requiere los middleware `auth` y `role:admin`.

El middleware `EnsureUserHasRole` aborta con `403` si el usuario no está activo o no tiene el rol requerido. `bootstrap/app.php` transforma el `403` HTML en una pantalla que explica el acceso restringido y ofrece regreso correcto según el rol.

### 6.2 Livewire

Livewire permite escribir una pantalla interactiva como una clase/componente PHP y una plantilla Blade, sin implementar manualmente un frontend SPA para ese CRUD.

Ejemplo conceptual:

1. El administrador escribe en un `wire:model="name"`.
2. Livewire sincroniza el valor con la propiedad pública `$name` del componente PHP.
3. Al enviar `wire:submit="save"`, Livewire hace una solicitud interna firmada/gestionada por Laravel.
4. El método `save()` valida con `$this->validate()`, persiste con Eloquent y Livewire vuelve a renderizar la parte necesaria del DOM.

Las páginas administrativas viven en `resources/views/pages/admin/` y usan componentes Livewire de una sola clase. Incluyen:

- `⚡dashboard.blade.php`: métricas y enlaces de administración.
- `⚡sectors.blade.php`: CRUD de sectores y asignación de coordinadores.
- `⚡resources.blade.php`: CRUD de recursos y su relación con sectores.
- `⚡shifts.blade.php`: programación administrativa de turnos.
- `⚡users.blade.php`: creación, edición, roles, sectores y activación de cuentas.

### 6.3 Vite de Laravel

`@vite(['resources/css/app.css', 'resources/js/app.js'])` en los layouts indica a Blade que cargue CSS y JavaScript compilados. En desarrollo, Vite ofrece Hot Module Replacement: al cambiar CSS/JS se actualiza el navegador sin recompilar todo manualmente. El servicio `laravel-vite` publica el servidor de desarrollo en `5174`.

Vite **no sustituye Laravel**. Vite transforma/entrega assets; Laravel sigue procesando rutas, sesión, Livewire y PHP.

## 7. React + Vite: Operaciones

### 7.1 Qué hace React

React construye la interfaz a partir de estado. En `src/App.jsx`, `useState` almacena usuario, sectores, recursos, reportes, formulario de turnos, evidencias y mensajes. Cuando se llama a un setter, React vuelve a renderizar la vista afectada.

`useEffect` se usa para efectos externos, principalmente consultar la API cuando existe sesión y cargar recursos del sector seleccionado.

El módulo permite:

- consultar sectores y recursos;
- filtrar recursos por estado;
- crear, editar, iniciar, completar o cancelar turnos;
- registrar volumen tras completar un turno;
- crear reportes y subir evidencias;
- agregar seguimiento si el rol es admin/coordinador;
- mostrar las pestañas permitidas por el rol.

### 7.2 Qué hace Vite

Vite es la herramienta de desarrollo y compilación de React. Durante desarrollo sirve módulos ES rápidamente; para producción, `npm run build` empaqueta, optimiza y escribe archivos estáticos en `frontend-react/dist/`.

Comandos:

```powershell
docker compose -f infrastructure/docker-compose.yml exec -T react npm test
docker compose -f infrastructure/docker-compose.yml exec -T react npm run lint
docker compose -f infrastructure/docker-compose.yml exec -T react npm run build
```

`src/api.js` centraliza `fetch`. El detalle importante es `credentials: 'include'`: ordena al navegador enviar cookies de sesión de Laravel. Para mutaciones, envía `X-XSRF-TOKEN`, que Laravel/Sanctum usa para prevenir CSRF.

## 8. Next.js + Prisma: indicadores e interrupciones

### 8.1 Qué aporta Next.js

Next.js es un framework React que puede renderizar en el servidor. `src/app/page.tsx` es un Server Component asíncrono: consulta datos antes de enviar HTML al navegador. Esto reduce exposición de credenciales y permite cálculos de indicadores cerca de la base de datos.

El módulo muestra:

- turnos del día, sectores atendidos, reportes pendientes;
- interrupciones activas y hogares afectados;
- volumen distribuido;
- historial de interrupciones con rango de fechas y paginación;
- reportes de prioridad alta/urgente;
- registro/restablecimiento de interrupciones.

`src/app/actions.ts` tiene Server Actions. Una acción se ejecuta en el servidor cuando el formulario HTML se envía. Antes de insertar o restablecer verifica la sesión, rol, sector asignado y consistencia entre reporte y sector.

### 8.2 Qué hace Prisma

Prisma es un cliente tipado de base de datos. `prisma/schema.prisma` describe tablas y relaciones que Prisma conoce. `src/lib/prisma.ts` crea un `PrismaClient` usando el adaptador MariaDB y la variable `DATABASE_URL`.

Prisma no reemplaza las migraciones de Laravel en este proyecto. Laravel es la fuente de verdad de migraciones; Prisma representa ese mismo esquema para Next.

Comandos:

```powershell
docker compose -f infrastructure/docker-compose.yml exec -T next npm run lint
docker compose -f infrastructure/docker-compose.yml exec -T next npm run build
curl.exe http://localhost:3000/api/health
```

El endpoint `GET /api/health` ejecuta `SELECT 1`. Si MariaDB responde, devuelve `{"status":"ok"}`; de lo contrario devuelve `503`.

## 9. API REST: contrato y uso

Base local: `http://localhost:8080/api/v1`.

| Método | Ruta | Qué hace | Roles |
|---|---|---|---|
| GET | `/health` | Estado básico de Laravel. | Público |
| POST | `/auth/login` | Login API para SPA, con CSRF. | Público |
| GET | `/auth/me` | Identidad/rol/sectores de la sesión. | Sesión |
| POST | `/auth/logout` | Cierra la sesión API. | Sesión |
| GET | `/sectors` | Sectores activos. | Sesión |
| GET | `/resources` | Recursos; filtros `status`, `type`, `sector_id`. | Sesión |
| GET/POST | `/shifts` | Consulta/programa turnos. | Sesión / admin-coordinator |
| PUT | `/shifts/{id}` | Edita fechas, recursos y notas. | Admin/coordinator autorizado |
| PATCH | `/shifts/{id}/status` | Transición de estado. | Admin/coordinator autorizado |
| PUT | `/shifts/{id}/result` | Guarda volumen del turno completado. | Admin/coordinator autorizado |
| GET/POST | `/reports` | Consulta/crea reportes. | Sesión |
| GET/PATCH | `/reports/{id}` | Detalle/cambio de un reporte. | Según acceso al reporte |
| POST | `/reports/{id}/followups` | Seguimiento y estado opcional. | Admin/coordinator del sector |
| POST | `/reports/{id}/evidences` | Evidencia JPEG, PNG o PDF privado. | Usuario autorizado |
| GET | `/reports/{report}/evidences/{evidence}/download` | URL/descarga autorizada. | Usuario autorizado |

Los controladores están en `app/Http/Controllers/Api/`; los Form Requests en `app/Http/Requests/` evitan mezclar validación con lógica de negocio; y los Resources en `app/Http/Resources/` deciden qué JSON se expone.

### Códigos HTTP que debes poder explicar

- `200 OK`: lectura/actualización correcta.
- `201 Created`: se creó un turno o reporte.
- `204 No Content`: logout correcto sin cuerpo de respuesta.
- `401 Unauthorized`: no hay una sesión válida.
- `403 Forbidden`: sí hay sesión, pero el rol/sector no permite la acción.
- `409 Conflict`: regla de negocio violada; por ejemplo, recurso reservado en horario solapado.
- `422 Unprocessable Content`: datos inválidos; por ejemplo, fin anterior al inicio.
- `429 Too Many Requests`: límite temporal; login y cargas de evidencia tienen throttling.

## 10. Turnos y prevención de conflictos

`ShiftConflictService` encapsula la regla más delicada: un recurso no puede pertenecer a dos turnos activos (`planned` o `in_progress`) cuyo tiempo se intersecte.

Dos intervalos se solapan si:

```text
turno_existente.inicio < turno_nuevo.fin
Y
turno_existente.fin > turno_nuevo.inicio
```

El servicio abre `DB::transaction()` y bloquea filas de recursos/turnos con `lockForUpdate()`. Esto evita que dos solicitudes concurrentes lean un recurso “libre” al mismo tiempo y ambas lo reserven. Si hay conflicto:

1. lanza `ShiftConflictException`;
2. registra el intento en `resource_conflict_logs`;
3. `bootstrap/app.php` lo traduce a JSON `409` con `code: RESOURCE_TIME_CONFLICT`.

Transiciones válidas:

```text
planned     → in_progress | cancelled
in_progress → completed   | cancelled
completed/cancelled → no cambian
```

Solo un turno `completed` puede guardar un `shift_result` con volumen distribuido.

## 11. Modelo de base de datos

### Tablas centrales

| Tabla | Propósito | Relaciones principales |
|---|---|---|
| `roles` | Catálogo `admin`, `coordinator`, `neighbor`. | 1:N con `user_accounts`. |
| `people` | Datos personales. | 1:1 con `user_accounts`. |
| `user_accounts` | Credenciales, rol, estado de cuenta. | N:1 rol/persona. |
| `sectors` | Zona comunitaria. | N:M recursos y coordinadores; 1:N turnos/reportes/interrupciones. |
| `resources` | Bomba, pozo, tanque, etc. | N:M sectores y turnos. |
| `shifts` | Turno programado. | N:1 sector/responsable; N:M recursos; 1:1 resultado. |
| `reports` | Problema comunicado. | N:1 sector/creador; 1:N seguimientos/evidencias. |
| `service_interruptions` | Corte/interrupción. | N:1 sector, creador y reporte opcional. |

### Tablas relacionales y de auditoría

| Tabla | Razón de existir |
|---|---|
| `resource_sector` | Un recurso puede servir a varios sectores y un sector usar varios recursos. |
| `shift_resources` | Un turno puede requerir múltiples recursos. |
| `sector_responsibles` | Un coordinador puede administrar más de un sector. |
| `report_followups` | Mantiene historial de acciones y cambios de estado. |
| `report_evidence` | Metadatos de archivos S3; no almacena el binario en MariaDB. |
| `resource_conflict_logs` | Auditoría de intentos de reserva conflictivos. |
| `shift_results` | Resultado real 1:1 de turnos completados. |
| `personal_access_tokens` | Tabla de Sanctum para tokens, aunque el flujo SPA usa cookie/sesión. |

### Llaves, índices y borrado

- `foreignId()->constrained()` crea la llave foránea: no permite referenciar una fila inexistente.
- `restrictOnDelete()` evita perder historial si se intenta borrar sector, rol o cuenta usada.
- `nullOnDelete()` conserva un turno aunque se elimine su responsable, dejando `responsible_user_id` como `NULL`.
- Los índices como `['status', 'starts_at', 'ends_at']` aceleran las búsquedas frecuentes de conflicto.
- Por eso el panel bloquea eliminar sectores/recursos con historial y recomienda inactivarlos.

## 12. Migraciones, seeders y datos de demostración

Una migración es código versionado que cambia el esquema de BD. Cada archivo tiene:

- `up()`: aplica el cambio, por ejemplo crear una tabla.
- `down()`: lo revierte, por ejemplo elimina esa tabla.

### Migrar la base de datos

```powershell
# Aplicar solo migraciones pendientes.
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan migrate

# Consultar qué migraciones se aplicaron.
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan migrate:status

# Sembrar roles, tres usuarios, sectores y recursos base.
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan db:seed --force

# Agregar datos visuales de simulación sin duplicarlos.
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan db:seed --class=SimulationDataSeeder --force
```

`DatabaseSeeder.php` crea las cuentas locales `admin`, `coordinador` y `vecino`, todas con contraseña `password`. Solo sirven para desarrollo.

> `php artisan migrate:fresh --seed` elimina y recrea todas las tablas. Úsalo únicamente en una BD local desechable; destruye los datos existentes.

### Respaldar y restaurar (“traer”) la base local

```powershell
# Exportar MariaDB a un archivo SQL en la raíz del proyecto.
docker compose -f infrastructure/docker-compose.yml exec -T mariadb `
  mariadb-dump -ucoquechas_app -pcoquechas_app_local coquechas_agua > respaldo_coquechas.sql

# Restaurar un respaldo SQL local.
Get-Content -Raw .\respaldo_coquechas.sql | docker compose -f infrastructure/docker-compose.yml exec -T mariadb `
  mariadb -ucoquechas_app -pcoquechas_app_local coquechas_agua
```

Para importar una BD externa, primero revisar que sea compatible con MariaDB y que no sobrescriba secretos o usuarios de producción. Nunca se debe copiar una BD de producción a un entorno local sin autorización y sin anonimizar datos personales.

## 13. Evidencias: Moto/S3

Laravel usa Flysystem con el disco `s3`. En local, `.env` apunta a `AWS_ENDPOINT=http://moto:5000`, que es Moto; en AWS se elimina ese endpoint y se usa un bucket S3 privado.

El flujo es:

1. React envía `multipart/form-data` a la API.
2. Laravel valida tamaño máximo (5 MB) y tipo permitido (JPEG, PNG, PDF).
3. Laravel guarda el archivo en S3/Moto, normalmente bajo el prefijo `reports/`.
4. `report_evidence` guarda nombre, tipo, tamaño, ruta y usuario que cargó el archivo.
5. La descarga requiere autorización; el bucket no es público.

En AWS, el prefijo esperado de evidencias es `reports/`. Una clave típica sería `reports/{report-id}/{uuid}.pdf`.

## 14. Variables de entorno

Los archivos `.env` contienen configuración que cambia por entorno y no deben ir a Git si incluyen secretos.

| Variable | Significado |
|---|---|
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | Entorno, trazas y URL de Laravel. |
| `DB_*` | Host, puerto, BD, usuario y contraseña de MariaDB. |
| `SANCTUM_STATEFUL_DOMAINS` | Orígenes SPA cuyas cookies reconoce Sanctum. |
| `SESSION_*` | Duración y alcance de la sesión. |
| `FILESYSTEM_DISK` | Disco para evidencias: `s3`. |
| `AWS_BUCKET`, `AWS_ENDPOINT` | Bucket y endpoint S3/Moto. |
| `DATABASE_URL` | Conexión de Prisma/Next. |
| `LARAVEL_INTERNAL_URL` | URL interna con que Next consulta `/auth/me`. |

En producción se usan Parameter Store/Secrets Manager y un rol IAM. No se guardan `AWS_ACCESS_KEY_ID` ni `AWS_SECRET_ACCESS_KEY` de producción en el repositorio.

## 15. Pruebas y QA

La suite Laravel usa SQLite en memoria para no modificar la MariaDB que contiene datos demo. Ejecutar exactamente:

```powershell
docker compose -f infrastructure/docker-compose.yml exec -T `
  -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= `
  laravel-php php artisan test
```

No ejecutar `php artisan test` sin esas variables dentro del contenedor de desarrollo: podría tomar la configuración MariaDB del entorno y alterar datos locales.

Qué se cubre en pruebas Feature:

- login, logout, roles y acceso prohibido;
- CRUD administrativo de sectores, recursos y usuarios;
- restricciones por sector de coordinadores;
- turnos, transiciones, edición y conflictos concurrentes;
- reportes, seguimientos, permisos y evidencias;
- API de catálogos.

También:

```powershell
docker compose -f infrastructure/docker-compose.yml exec -T react npm test
docker compose -f infrastructure/docker-compose.yml exec -T react npm run build
docker compose -f infrastructure/docker-compose.yml exec -T next npm run lint
docker compose -f infrastructure/docker-compose.yml exec -T next npm run build
```

La auditoría de ejecución se conserva en `docs/AUDITORIA_QA_LOCAL.md`.

## 16. Cómo crear una funcionalidad nueva

### Caso A: nuevo catálogo administrable en Laravel

1. Crear migración: `php artisan make:migration create_x_table`.
2. Definir columnas, índices y llaves foráneas en `database/migrations/`.
3. Migrar en Docker.
4. Crear modelo: `php artisan make:model X` y relaciones Eloquent.
5. Crear página Livewire y ruta protegida bajo el grupo `admin`.
6. Validar entradas con reglas explícitas; nunca confiar solo en el navegador.
7. Añadir pruebas Livewire/Feature para crear, editar, permisos y borrado protegido.
8. Añadir navegación y documentación.

### Caso B: nueva acción operativa consumida por React

1. Crear/actualizar Form Request y controlador API de Laravel.
2. Aplicar autorización por rol/sector en el servidor.
3. Registrar la ruta bajo `auth:sanctum` en `routes/api.php`.
4. Crear Resource si el JSON necesita forma estable.
5. Escribir pruebas API para éxito, `401`, `403`, `422` y conflictos aplicables.
6. Crear función en `frontend-react/src/api.js` con CSRF y `credentials: 'include'`.
7. Actualizar estado/UX de `App.jsx` y probar build/Vitest.

### Caso C: nueva métrica o Server Action de Next

1. Confirmar que Prisma conoce la tabla/relación requerida.
2. Consultar solo columnas necesarias y filtrar por sectores autorizados.
3. Para escritura, crear Server Action en `src/app/actions.ts`.
4. Revalidar sesión con `currentUser()` dentro de la acción; la UI no es la frontera de seguridad.
5. Validar `FormData` con Zod, escribir mediante Prisma y llamar `revalidatePath('/')`.
6. Redirigir con mensaje seguro de éxito/error.
7. Ejecutar lint/build y pruebas que correspondan.

## 17. Sintaxis que conviene saber explicar

### Punto y coma `;`

En PHP, `;` termina una instrucción:

```php
$sector = Sector::query()->findOrFail($sectorId);
```

Sin ese punto y coma, PHP no sabe dónde termina la orden y produce error de sintaxis. En JavaScript moderno el punto y coma puede inferirse en muchos casos, pero se usa de forma consistente en TypeScript/Next para eliminar ambigüedades y mantener estilo uniforme.

### Llaves `{}` y paréntesis `()`

- `()` agrupa argumentos o condiciones: `if ($user->hasRole('admin'))`.
- `{}` delimita un bloque: el código que pertenece al `if`, función, clase o bucle.

```php
if ($user->hasRole('admin')) {
    return;
}
```

### Flecha `=>`

En PHP, `=>` asocia clave y valor de un arreglo:

```php
['status' => 'active']
```

En JavaScript, `=>` crea una función flecha:

```js
items.map((item) => item.id)
```

No significan exactamente lo mismo; el lenguaje determina su significado.

### `?->`, `??` y `?:` de PHP

- `$user?->person`: operador null-safe; si `$user` es `null`, devuelve `null` sin error.
- `$value ?? 'valor'`: usa el valor derecho solo si el izquierdo es `null`.
- `$value ?: 'valor'`: usa el derecho si el izquierdo es “falsy” (vacío, `false`, `0`, `null`).

### Tipos y firmas

```php
public function store(Request $request): RedirectResponse
```

- `public`: el framework puede llamar el método.
- `function store`: nombre/acción del método.
- `(Request $request)`: parámetro tipado que representa HTTP.
- `: RedirectResponse`: el método promete devolver una redirección.

El tipado hace que errores se detecten antes y documenta intención.

### `async`, `await` y `Promise` en JavaScript

Una consulta `fetch` tarda; no bloquea el navegador. `await` espera su resultado dentro de una función `async`:

```js
const response = await fetch(url, options)
```

Esto hace que el código sea legible sin ocultar que opera de manera asíncrona.

### JSX/TSX

JSX/TSX parece HTML, pero es JavaScript/TypeScript que React convierte a llamadas de renderizado:

```jsx
{user.role === 'admin' && <a href="/admin">Administración</a>}
```

La expresión entre llaves se evalúa: si no es admin, React no renderiza el enlace. Aun así, el backend debe proteger la ruta; ocultar un botón no es una autorización de seguridad.

## 18. Flujo completo de ejemplo: reporte con evidencia

1. Vecino abre `8080/login`, inicia sesión y llega a `5175`.
2. React obtiene identidad mediante `/api/v1/auth/me`.
3. El vecino selecciona sector, categoría, prioridad y descripción.
4. `App.jsx` llama `createReport` en `api.js`.
5. Laravel `ReportController` recibe la solicitud; su Form Request valida campos.
6. El controlador crea `reports` con `created_by_user_id` igual al usuario autenticado.
7. Si se adjuntaron archivos, React ejecuta `uploadEvidences` con `FormData`.
8. Laravel valida tipo/tamaño, guarda el objeto en Moto/S3 privado y guarda metadata en `report_evidence`.
9. Un coordinador de ese sector agrega seguimiento; un coordinador de otro sector recibe `403`.
10. Next puede mostrar el reporte prioritario entre sus indicadores si pertenece a un sector autorizado.

## 19. Principios que debes defender

- **Una sola identidad**: las credenciales no se duplican entre módulos.
- **Autorización en servidor**: React/Next muestran UI por rol, pero Laravel/Server Actions vuelven a validar.
- **Datos consistentes**: transacciones, locks, llaves foráneas e índices protegen turnos e historial.
- **Evidencias privadas**: los archivos no son URLs públicas; se autorizan antes de descargar.
- **Entorno reproducible**: Docker Compose levanta los mismos servicios locales para todos.
- **Cambios rastreables**: migraciones, seeders, pruebas y Git permiten repetir y auditar cambios.
- **Separación de responsabilidades**: cada módulo tiene una función clara, pero la BD y sesión son compartidas bajo controles explícitos.

## 20. Lecturas relacionadas del repositorio

- `README.md`: arranque rápido local.
- `docs/MANUAL_FUNCIONAL.md`: uso por rol y recorrido de demostración.
- `docs/CONTRATO_API.md`: contrato compacto de API.
- `docs/AUDITORIA_QA_LOCAL.md`: pruebas ejecutadas y resultados.
- `docs/DESPLIEGUE_AWS.md`: arquitectura y operación de AWS.
- `docs/PROGRESO.md`: fases realizadas.

