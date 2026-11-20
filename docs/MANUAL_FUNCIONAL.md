# Manual funcional — Coquechas Agua

Este documento explica cómo operar la solución completa en local para pruebas QA. Coquechas organiza la distribución comunitaria de agua, reportes de problemas e interrupciones del servicio.

## 1. Vista general

La solución tiene tres aplicaciones que usan la misma base de datos MariaDB (`coquechas_agua`):

| Módulo | URL local | Uso principal |
|---|---:|---|
| Laravel + Livewire | `http://localhost:8080` | Administración de sectores y recursos. |
| React | `http://localhost:5175` | Operación de turnos y reportes. |
| Next.js | `http://localhost:3000` | Interrupciones e indicadores operativos. |

Laravel conserva las reglas críticas, usuarios, permisos, API y migraciones. React usa exclusivamente la API Laravel. Next valida la misma sesión Laravel y consulta/escribe solo las tablas necesarias para interrupciones e indicadores.

## 2. Inicio local

Desde la raíz del repositorio:

```powershell
docker compose -f infrastructure/docker-compose.yml up -d
docker compose -f infrastructure/docker-compose.yml exec laravel-php php artisan migrate --seed --force
```

Para agregar una carga demostrativa completa (15 reportes, 15 turnos y 15 interrupciones por sector activo), ejecute:

```powershell
docker compose -f infrastructure/docker-compose.yml exec laravel-php php artisan db:seed --class=SimulationDataSeeder --force
```

La carga es idempotente: puede volver a ejecutarse sin duplicar sus registros, identificados con `[SIMULACION]`.

Servicios auxiliares:

- MariaDB: persistencia interna de Docker.
- Moto: emulación local de S3 en `http://localhost:5000`.
- Mailpit: opcional, perfil `mail`, en `http://localhost:8025`.

## 3. Usuarios demo y responsabilidades

| Usuario | Contraseña local | Qué debe hacer |
|---|---|---|
| `admin` | `password` | Configurar sectores y recursos; puede operar todos los sectores, turnos, reportes e interrupciones. |
| `coordinador` | `password` | Operar únicamente los sectores que el administrador le asigna. Puede crear turnos, actualizar su estado, registrar volumen, atender reportes e interrupciones de esos sectores. |
| `vecino` | `password` | Consultar información y crear/consultar únicamente sus propios reportes. |

El seeder prepara tres sectores activos y dos recursos de demostración. El coordinador queda asignado a Sector Norte y Sector Sur.

## 4. Flujo general del negocio

```text
Admin configura sectores/recursos
        ↓
Admin asigna coordinador a los sectores
        ↓
Coordinador programa turno con recursos disponibles
        ↓
Sistema rechaza automáticamente horarios superpuestos
        ↓
Coordinador inicia y completa el turno
        ↓
Coordinador registra el volumen distribuido
        ↓
Next actualiza indicadores reales

Vecino reporta problema → adjunta evidencia → coordinador atiende → seguimiento/cierre
                                                   ↓
                              Coordinador registra interrupción y luego la restablece
```

## 5. Módulo administrativo: Laravel + Livewire

Acceso: `http://localhost:8080/login`. Está reservado para `admin`.

### Sectores

Ruta: `/admin/sectores`.

El administrador debe:

1. Crear cada zona de distribución con nombre, descripción, ubicación y estado.
2. Asignar uno o más coordinadores responsables.
3. Editar el sector si cambia su información o marcarlo como inactivo si deja de atenderse.
4. Eliminar solo sectores sin historial operativo.

Un sector con turnos, reportes o interrupciones no se elimina: se inactiva para preservar la trazabilidad.

### Recursos

Ruta: `/admin/recursos`.

El administrador registra bombas, pozos, tanques u otros medios de distribución. Para cada recurso debe indicar tipo, capacidad, unidad, ubicación, estado y sectores donde puede utilizarse.

Estados:

- `available`: puede seleccionarse en nuevos turnos.
- `maintenance`: no se puede usar en nuevos turnos.
- `inactive`: queda fuera de operación, conservando historial.

Un recurso usado por un turno no se elimina; debe marcarse inactivo o en mantenimiento.

## 6. Módulo operativo: React

Acceso: `http://localhost:5175`. Inicie sesión con cualquiera de los tres usuarios demo.

### Consultas para todos los roles

Todos pueden ver sectores activos, recursos visibles y los turnos/reportes que les autoriza su rol. El filtro de inventario permite consultar todos los recursos, disponibles, en mantenimiento o inactivos.

### Turnos de agua

Solo `admin` y `coordinador` ven el formulario de programación.

1. Seleccione un sector permitido.
2. Elija inicio y fin; el fin debe ser posterior al inicio.
3. Seleccione uno o más recursos disponibles y asociados a ese sector.
4. Agregue notas y presione **Programar turno**.

El servidor valida de forma transaccional que el recurso no esté ya reservado por un turno `planned` o `in_progress` con horario solapado. Si existe conflicto, no se crea el turno y React muestra el aviso correspondiente.

Estados válidos:

```text
planned → in_progress → completed
planned → cancelled
in_progress → cancelled
```

Al completar un turno, el responsable registra el volumen distribuido. Este valor alimenta el indicador del panel Next.

### Reportar un problema

Todos los usuarios pueden crear un reporte:

1. Seleccione sector, categoría, prioridad y descripción.
2. Opcionalmente adjunte hasta cinco archivos JPEG, PNG o PDF; cada archivo admite un máximo de 5 MB.
3. Presione **Enviar reporte**.

Los archivos se guardan en almacenamiento privado con esta estructura:

```text
reports/{report-id}/{uuid}-{nombre-normalizado}.{extension}
```

En local se usa Moto. En AWS, Laravel los guardará en el bucket privado S3. Los usuarios autorizados reciben una URL temporal para descargarlos; el bucket no debe ser público.

### Seguimiento de reportes

El vecino ve únicamente sus reportes. El administrador ve todos y el coordinador solo los de sus sectores asignados.

Administrador o coordinador deben escribir un seguimiento y, si corresponde, cambiar el estado:

```text
pending → in_progress → resolved → closed
```

## 7. Panel de interrupciones e indicadores: Next.js

Acceso: `http://localhost:3000`.

Primero inicie sesión en React o Laravel con `admin` o `coordinador`. Next reutiliza la cookie Laravel; un vecino no tiene acceso al panel.

### Registrar una interrupción

1. Seleccione un sector permitido.
2. Indique causa, fecha/hora de inicio, hogares afectados y descripción opcional.
3. Presione **Registrar interrupción**.

La interrupción queda activa mientras `restored_at` esté vacío. Solo el administrador o el coordinador responsable del sector puede crearla o restablecerla.

### Restablecer el servicio

En **Interrupciones activas**, presione **Restablecer**. El sistema registra la hora de restauración y retira el aviso de los indicadores activos sin borrar el historial.

### Indicadores

El panel calcula datos reales para el día actual:

- Turnos programados o en curso.
- Sectores atendidos por turnos completados.
- Reportes pendientes o en proceso.
- Interrupciones activas.
- Volumen distribuido registrado en turnos completados.
- Hogares afectados por interrupciones activas.

También muestra las interrupciones recientes y reportes urgentes o de prioridad alta. Un coordinador solo ve información de sus sectores; el administrador ve el total.

## 8. Reglas de seguridad y autorización

- Las interfaces ocultan opciones no permitidas, pero Laravel y las Server Actions validan el permiso nuevamente.
- React nunca se conecta directamente a MariaDB.
- Next usa un usuario MariaDB limitado: lectura de datos operativos necesarios y escritura solo para `service_interruptions`.
- Las validaciones API usan códigos semánticos: `401` no autenticado, `403` sin permiso, `409` conflicto de recurso y `422` datos inválidos.
- El inicio de sesión está limitado contra intentos excesivos.

## 9. Recorrido recomendado para una demostración QA

1. Entre a Laravel como `admin`; cree o edite un sector y asigne al `coordinador`.
2. Cree o revise un recurso y asígnelo al sector.
3. Inicie sesión en React como `coordinador`; programe un turno con ese recurso.
4. Intente un segundo turno solapado con el mismo recurso; confirme el rechazo.
5. Inicie, complete el primer turno y registre volumen distribuido.
6. Inicie sesión como `vecino`; cree un reporte y adjunte un PDF, PNG o JPEG válido.
7. Regrese como `coordinador`; agregue seguimiento y cambie el reporte a `in_progress` o `resolved`.
8. Abra Next, registre una interrupción y confirme que cambian hogares afectados e interrupciones activas.
9. Restablezca el servicio y confirme que la interrupción deja de estar activa y se conserva en historial.

### Ejecutar pruebas sin modificar los datos de QA

Los tests Laravel usan SQLite en memoria y deben invocarse con estas variables explícitas dentro del contenedor, para no usar la MariaDB persistente de Docker:

```powershell
docker compose -f infrastructure/docker-compose.yml exec -T `
  -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= `
  laravel-php php artisan test
```

## 10. Límites actuales antes de AWS

La solución está preparada para QA local, pero aún faltan funcionalidades antes del despliegue productivo:

- Edición completa de un turno existente.
- Filtros de periodo y paginación en Next.
- Selector visual de reporte relacionado al crear una interrupción.
- Pruebas de componentes React y Server Actions Next.
- Compose e imágenes de producción, health check de Next, DNS, HTTPS y recursos AWS.

El detalle de pruebas ejecutadas y pendientes está en [AUDITORIA_QA_LOCAL.md](AUDITORIA_QA_LOCAL.md).
