# Comandos de operación — Coquechas Agua

Guía práctica de los comandos importantes para desarrollar, validar y desplegar el proyecto. Los comandos locales están escritos para **Windows PowerShell** y se ejecutan desde la raíz del repositorio, salvo que se indique otra carpeta.

> No subas a Git archivos .env, contraseñas, llaves .pem ni secretos de AWS. Las credenciales MariaDB indicadas aquí son únicamente las del entorno local Docker.

## 1. Navegación

### Entrar a la raíz

**Sirve para:** colocarte en la carpeta del proyecto antes de usar Docker, Git o cualquier módulo.

~~~
cd "C:\Users\enman\Documents\Universidad\Octavo Semestre\Programación web 2\proyecto-final\coquechas-agua"
~~~

### Ver archivos y estructura

**Sirve para:** confirmar dónde estás y ubicar carpetas o archivos.

~~~
Get-ChildItem
tree /F
~~~

### Buscar texto o código

**Sirve para:** encontrar rutas, endpoints, variables, modelos o mensajes de error.

~~~
rg -n "texto a buscar"
rg -n "api/v1" backend-laravel frontend-react dashboard-next
rg -n "docker compose" docs infrastructure README.md
~~~

### Abrir en VS Code

**Sirve para:** abrir todo el repositorio o uno de los módulos.

~~~
code .
code .\backend-laravel
code .\frontend-react
code .\dashboard-next
~~~

| Ruta | Sirve para |
|---|---|
| backend-laravel/ | Administración Livewire, autenticación, API y migraciones. |
| frontend-react/ | Operaciones que consumen la API Laravel. |
| dashboard-next/ | Indicadores, interrupciones y Prisma. |
| infrastructure/docker-compose.yml | Entorno local Docker. |
| infrastructure/scripts/deploy.sh | Despliegue controlado desde EC2. |
| docs/ | Manuales, API, QA y AWS. |

## 2. Git y GitHub

### Revisar cambios

**Sirve para:** conocer los archivos modificados, nuevos o preparados antes de hacer commit.

~~~
git status
git diff
git diff --staged
~~~

### Consultar historial

**Sirve para:** conocer el último commit o identificar una versión anterior.

~~~
git log --oneline -10
git log -1 --oneline
git branch --show-current
~~~

### Descargar cambios remotos

**Sirve para:** actualizar la rama local sin crear merges automáticos.

~~~
git pull --ff-only origin main
~~~

Si falla, revisa primero <code>git status</code>. No uses <code>git reset --hard</code> como solución rápida: puede borrar trabajo local.

### Crear y publicar un cambio

**Sirve para:** enviar código o documentación a GitHub.

~~~
git add ruta/del/archivo
git commit -m "feat: descripcion del cambio"
git push origin main
~~~

Para preparar todos los cambios que ya revisaste:

~~~
git add .
git commit -m "docs: actualizar guia de operacion"
git push origin main
~~~

> <code>git push origin main</code> publica cambios en **GitHub**. No actualiza automáticamente los contenedores de EC2.

### Verificar remoto

**Sirve para:** confirmar que Git apunta al repositorio correcto.

~~~
git remote -v
~~~

## 3. Docker local

| Aplicación | Dirección |
|---|---|
| Laravel y administración | http://localhost:8080/login |
| React / Operaciones | http://localhost:5175 |
| Next.js / Indicadores | http://localhost:3000 |
| Moto / emulador S3 | http://localhost:5000 |

### Iniciar los servicios

**Sirve para:** levantar MariaDB, Laravel, React, Next.js, Moto y servicios auxiliares.

~~~
docker compose -f infrastructure/docker-compose.yml up -d
~~~

### Reconstruir contenedores

**Sirve para:** aplicar cambios en Dockerfile, dependencias o configuración de Docker.

~~~
docker compose -f infrastructure/docker-compose.yml up -d --build
~~~

### Ver estado y puertos

**Sirve para:** comprobar que todos los servicios están activos o detectar cuál falló.

~~~
docker compose -f infrastructure/docker-compose.yml ps
docker ps
docker info
~~~

### Ver logs en tiempo real

**Sirve para:** investigar fallos. Usa Ctrl+C para cerrar los logs sin detener el contenedor.

~~~
docker compose -f infrastructure/docker-compose.yml logs -f laravel-php
docker compose -f infrastructure/docker-compose.yml logs -f laravel-nginx
docker compose -f infrastructure/docker-compose.yml logs -f react
docker compose -f infrastructure/docker-compose.yml logs -f next
docker compose -f infrastructure/docker-compose.yml logs -f mariadb
~~~

### Ver logs recientes

**Sirve para:** consultar errores sin dejar la terminal en modo seguimiento.

~~~
docker compose -f infrastructure/docker-compose.yml logs --tail=100 laravel-php
~~~

### Reiniciar servicios

**Sirve para:** recuperar un contenedor con error sin borrar sus datos.

~~~
docker compose -f infrastructure/docker-compose.yml restart laravel-php
docker compose -f infrastructure/docker-compose.yml restart react
docker compose -f infrastructure/docker-compose.yml restart next
docker compose -f infrastructure/docker-compose.yml restart
~~~

### Abrir una terminal dentro de un contenedor

**Sirve para:** ejecutar comandos de diagnóstico en Linux dentro de Docker.

~~~
docker compose -f infrastructure/docker-compose.yml exec laravel-php sh
docker compose -f infrastructure/docker-compose.yml exec react sh
docker compose -f infrastructure/docker-compose.yml exec next sh
~~~

### Apagar contenedores y conservar la base

**Sirve para:** detener el entorno sin eliminar el volumen persistente de MariaDB.

~~~
docker compose -f infrastructure/docker-compose.yml down
~~~

### Borrar también los datos locales

**Sirve para:** reiniciar el entorno desde cero. **Elimina la base local** y no se puede deshacer mediante Docker.

~~~
docker compose -f infrastructure/docker-compose.yml down -v
docker compose -f infrastructure/docker-compose.yml up -d
~~~

Después ejecuta migraciones y seeders.

## 4. Laravel, migraciones y pruebas

Los comandos se ejecutan en el contenedor <code>laravel-php</code>; no necesitas instalar PHP globalmente en Windows.

### Consultar Artisan

**Sirve para:** confirmar que Laravel funciona y conocer sus comandos.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan --version
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan list
~~~

### Revisar y aplicar migraciones

**Sirve para:** ver qué tablas/cambios faltan y aplicar el esquema actual.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan migrate:status
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan migrate
~~~

### Cargar datos iniciales o simulados

**Sirve para:** crear cuentas demo y catálogos, o agregar registros de prueba.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan db:seed
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan db:seed --class=SimulationDataSeeder --force
~~~

La cuenta demo administrativa es <code>admin</code> / <code>password</code>.

### Limpiar cachés

**Sirve para:** volver a cargar configuración, rutas y vistas tras modificar .env, rutas, Blade o config.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan optimize:clear
~~~

### Generar migración, modelo o controlador

**Sirve para:** crear archivos con las convenciones de Laravel.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan make:migration create_ejemplo_table
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan make:model Ejemplo
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php php artisan make:controller EjemploController
~~~

### Usar Tinker

**Sirve para:** consultar modelos Laravel interactivamente. Puede cambiar datos, úsalo con cuidado.

~~~
docker compose -f infrastructure/docker-compose.yml exec laravel-php php artisan tinker
~~~

Ejemplos dentro de Tinker:

~~~php
App\Models\User::query()->get(['id', 'username', 'is_active']);
App\Models\Sector::query()->count();
~~~

### Ejecutar pruebas sin alterar datos demo

**Sirve para:** usar SQLite temporal en memoria en lugar de la MariaDB local persistente.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= laravel-php php artisan test
~~~

### Formatear PHP

**Sirve para:** aplicar el estándar de formato antes de hacer commit.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T laravel-php vendor/bin/pint --format agent
~~~

## 5. MariaDB local

### Abrir la consola SQL

**Sirve para:** inspeccionar tablas y ejecutar consultas directamente en la base local.

~~~
docker compose -f infrastructure/docker-compose.yml exec mariadb mariadb -ucoquechas_app -pcoquechas_app_local coquechas_agua
~~~

Ejemplos dentro de MariaDB:

~~~sql
SHOW TABLES;
DESCRIBE shifts;
SELECT id, username, is_active FROM user_accounts;
SELECT id, name, status FROM sectors;
EXIT;
~~~

### Ejecutar una consulta desde PowerShell

**Sirve para:** verificar rápidamente un dato sin abrir la consola interactiva.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T mariadb mariadb -ucoquechas_app -pcoquechas_app_local coquechas_agua -e "SELECT id, name, status FROM resources;"
~~~

### Crear respaldo local

**Sirve para:** guardar una copia SQL antes de cambios de datos. El archivo no se debe subir a Git.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T mariadb mariadb-dump -ucoquechas_app -pcoquechas_app_local coquechas_agua > respaldo_coquechas.sql
~~~

### Restaurar un respaldo local

**Sirve para:** cargar una copia SQL anterior. Puede sobrescribir o agregar datos; revisa el archivo antes de ejecutarlo.

~~~
Get-Content -Raw .\respaldo_coquechas.sql | docker compose -f infrastructure/docker-compose.yml exec -T mariadb mariadb -ucoquechas_app -pcoquechas_app_local coquechas_agua
~~~

## 6. React, Next.js y verificación

### Validar React

**Sirve para:** ejecutar pruebas, lint y compilación de Operaciones.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T react npm test
docker compose -f infrastructure/docker-compose.yml exec -T react npm run lint
docker compose -f infrastructure/docker-compose.yml exec -T react npm run build
~~~

### Validar Next.js

**Sirve para:** ejecutar lint y compilación de Indicadores.

~~~
docker compose -f infrastructure/docker-compose.yml exec -T next npm run lint
docker compose -f infrastructure/docker-compose.yml exec -T next npm run build
~~~

### Consultar salud local

**Sirve para:** confirmar que cada servicio responde por HTTP.

~~~
Invoke-WebRequest http://localhost:8080/up
Invoke-WebRequest http://localhost:5175/health
Invoke-WebRequest http://localhost:3000/api/health
~~~

## 7. EC2: entrar y navegar

En producción se recomienda AWS Systems Manager Session Manager, sin abrir SSH. Los comandos requieren que la instancia y permisos AWS ya estén configurados.

### Entrar por Session Manager

**Sirve para:** abrir una terminal segura en EC2. Requiere AWS CLI configurada y el rol SSM en la instancia.

~~~powershell
aws ssm start-session --target i-REEMPLAZA_CON_ID_DE_INSTANCIA --region us-east-1
~~~

### Entrar por SSH, si fue autorizado

**Sirve para:** conectarte con llave privada a una EC2 que tenga SSH habilitado temporalmente. Restringe el puerto 22 a tu IP.

~~~powershell
ssh -i "C:\ruta\segura\coquechas.pem" ec2-user@IP_O_DNS_DE_LA_EC2
~~~

### Moverse al repositorio de EC2

**Sirve para:** ir al clon que usa el despliegue.

~~~bash
cd /opt/coquechas/coquecha_web_project
pwd
ls -la
git status
git log -1 --oneline
~~~

### Consultar contenedores de producción

**Sirve para:** verificar aplicaciones desplegadas y revisar logs.

~~~bash
docker compose -f infrastructure/docker-compose.production.yml ps
docker compose -f infrastructure/docker-compose.production.yml logs --tail=100 laravel-nginx
docker compose -f infrastructure/docker-compose.production.yml logs --tail=100 react
docker compose -f infrastructure/docker-compose.production.yml logs --tail=100 next
~~~

> Requiere que exista y esté configurado infrastructure/docker-compose.production.yml. No uses el Compose local en EC2 de producción: incluye herramientas de desarrollo como Moto y Vite.

## 8. Script de despliegue

El script real es <code>infrastructure/scripts/deploy.sh</code>.

**Importante:** no sube cambios desde Windows. Primero ejecuta <code>git push origin main</code> localmente. Después, en EC2, el script descarga <code>origin/main</code>, crea imágenes, aplica migraciones cuando corresponde, actualiza servicios, valida salud y guarda tags para rollback.

### Validar y habilitar el script

**Sirve para:** comprobar sintaxis y, solo la primera vez, permitir su ejecución directa.

~~~bash
cd /opt/coquechas/coquecha_web_project
bash -n infrastructure/scripts/deploy.sh
chmod +x infrastructure/scripts/deploy.sh
~~~

### Desplegar todo

**Sirve para:** actualizar Laravel, React y Next.js al último commit de GitHub.

~~~bash
./infrastructure/scripts/deploy.sh all
~~~

### Desplegar un módulo

**Sirve para:** evitar reconstruir servicios que no cambiaron.

~~~bash
./infrastructure/scripts/deploy.sh laravel
./infrastructure/scripts/deploy.sh react
./infrastructure/scripts/deploy.sh next
~~~

### Revertir una versión

**Sirve para:** iniciar una imagen previamente registrada. Sustituye el tag por el SHA que informó el script o que aparece en el historial.

~~~bash
./infrastructure/scripts/deploy.sh rollback laravel <tag-del-commit>
./infrastructure/scripts/deploy.sh rollback react <tag-del-commit>
./infrastructure/scripts/deploy.sh rollback next <tag-del-commit>
./infrastructure/scripts/deploy.sh rollback all <tag-del-commit>
~~~

### Flujo seguro para publicar

**Sirve para:** recordar el orden correcto.

~~~text
1. Modificar código local.
2. Ejecutar pruebas, lint y build necesarios.
3. Revisar git status y git diff.
4. Ejecutar git add, git commit y git push origin main.
5. Entrar a EC2 mediante Session Manager.
6. Ejecutar bash -n infrastructure/scripts/deploy.sh.
7. Ejecutar ./infrastructure/scripts/deploy.sh <modulo|all>.
8. Revisar docker compose ... ps, logs y health checks.
~~~

## 9. Documentos relacionados

| Documento | Sirve para |
|---|---|
| docs/GUIA_TECNICA_COMPLETA.md | Comprender arquitectura, código, base de datos y módulos. |
| docs/MANUAL_FUNCIONAL.md | Probar el sistema como usuario. |
| docs/CONTRATO_API.md | Consultar endpoints y respuestas. |
| docs/AUDITORIA_QA_LOCAL.md | Seguir validaciones de calidad locales. |
| docs/DESPLIEGUE_AWS.md | Configurar VPC, EC2, RDS, S3, ALB, HTTPS y AWS. |
| docs/PROGRESO.md | Consultar fases terminadas y siguiente fase. |

## 10. Ante un error

1. Ejecuta <code>docker compose -f infrastructure/docker-compose.yml ps</code>.
2. Consulta <code>logs -f &lt;servicio&gt;</code> del contenedor afectado.
3. Prueba su URL con <code>Invoke-WebRequest</code>.
4. Antes de borrar o revertir archivos, ejecuta <code>git status</code>.
5. Ante problemas de datos, consulta <code>php artisan migrate:status</code> y MariaDB.
6. No uses <code>docker compose down -v</code> ni <code>git reset --hard</code> salvo que quieras perder datos locales o cambios sin commit.

