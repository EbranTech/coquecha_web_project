# Despliegue AWS — Coquechas Agua

> Guía operativa para el entorno **production**. Parte del estado actual: Laravel/Livewire y API, React/Vite y Next.js comparten MariaDB; los archivos de evidencia se almacenarán en S3. Moto y MariaDB del `docker-compose.yml` actual son exclusivamente locales y no se despliegan en AWS.

## 1. Resultado esperado

Usar una región única para todos los recursos (por ejemplo, `us-east-1`) y un dominio real, representado aquí por `coquechas.tld`.

```text
Internet
  └─ Route 53: app, api, operaciones y panel.coquechas.tld
       └─ ALB público (ACM, HTTPS)
            ├─ app.coquechas.tld y api.coquechas.tld -> TG Laravel :8080
            ├─ operaciones.coquechas.tld             -> TG React   :8081
            └─ panel.coquechas.tld                   -> TG Next    :3000
                 └─ EC2 privadas con Docker Compose
                      ├─ RDS MariaDB privada :3306
                      ├─ S3 privado (evidencias)
                      ├─ ECR (imágenes)
                      ├─ SSM Parameter Store (configuración y secretos)
                      └─ CloudWatch (logs, métricas y alarmas)
```

Los tres *target groups* pueden registrar las mismas instancias EC2, cada uno en su puerto. No se abre ninguno de esos puertos a Internet: el grupo de seguridad de aplicación acepta únicamente tráfico del grupo de seguridad del ALB. Para una entrega mínima se puede usar una EC2; para demostración de balanceo, un Auto Scaling Group de mínimo dos instancias en dos zonas de disponibilidad (AZ).

## 2. Nombres y valores que se deben decidir antes de crear recursos

No continúe sin completar esta tabla. Sustituya los ejemplos en todos los servicios y nunca registre secretos en Git.

| Concepto | Producción propuesta |
|---|---|
| Región | `us-east-1` (o la región autorizada por el curso) |
| Dominio | `coquechas.tld` |
| Etiquetas comunes | `Project=coquechas-agua`, `Environment=production`, `Owner=<equipo>` |
| VPC / CIDR | `coquechas-prod-vpc` / `10.20.0.0/16` |
| Bucket | `coquechas-agua-evidences-production-<account-id>` |
| Base | `coquechas_agua` |
| ALB | `coquechas-prod-alb` |
| ECR | `coquechas/laravel`, `coquechas/react`, `coquechas/next` |
| Prefijo de parámetros | `/coquechas/production/` |

## 3. Inventario final de AWS

| Servicio | Debe quedar creado | Propósito |
|---|---|---|
| IAM + IAM Identity Center | Usuarios/grupos de equipo y roles de EC2 | Acceso de personas y permisos temporales de aplicaciones. |
| VPC, Internet Gateway, NAT Gateway, subredes y tablas de rutas | VPC con 2 públicas y 2 privadas de aplicación; 2 privadas de base | Aislar Internet, aplicaciones y datos. |
| Security Groups | `sg-alb`, `sg-app`, `sg-db` | Permitir solo los flujos necesarios. |
| ECR | Tres repositorios privados | Versionar y descargar imágenes Docker. |
| EC2 + Auto Scaling Group + Launch Template | 1–2 instancias Amazon Linux 2023 | Ejecutar Docker Compose de producción. |
| RDS for MariaDB | Instancia privada, backups y cifrado | Base de datos única persistente. |
| S3 | Bucket privado con Versioning y cifrado | Evidencias de reportes. |
| Systems Manager Parameter Store | Parámetros `String` y `SecureString` | Variables y secretos fuera de las imágenes. |
| Application Load Balancer | Listeners 80/443, 3 target groups y reglas por host | TLS, salud y ruteo por subdominio. |
| ACM | Certificado público para el dominio | HTTPS en el ALB. |
| Route 53 | Hosted zone y cuatro alias A/AAAA al ALB | DNS público. |
| CloudWatch + SNS | Grupos de log, dashboard y alarmas | Observabilidad y avisos. |
| KMS | Clave administrada de AWS o CMK del proyecto | Cifrado de secretos, S3 y RDS. |

No son necesarios para el MVP: CloudFront, Lambda, ECS, API Gateway, ElastiCache, Cognito, SES y una base separada por aplicación. No se deben crear solo por “usar más AWS”.

## 4. Orden de activación en la consola

### 4.1 Cuenta, región y costos

1. Entre con el usuario raíz solo para habilitar MFA, crear un contacto de facturación y activar **AWS IAM Identity Center**. Después, cree un usuario/grupo administrador temporal para el equipo y no use raíz para desplegar.
2. En la barra superior seleccione la región acordada. RDS, EC2, ALB, S3 (salvo el nombre global) y Parameter Store se crean o consultan en esa región.
3. Abra **Billing and Cost Management → Budgets → Create budget** y cree un presupuesto mensual con alertas al 50 %, 80 % y 100 %. Active también Cost Explorer. NAT Gateway, ALB, RDS y transferencia pueden generar costo aunque no haya visitas.
4. Aplique las etiquetas definidas en la tabla anterior a cada recurso. Facilitan localizar, auditar y eliminar el entorno.

### 4.2 VPC y subredes

En **VPC → Your VPCs → Create VPC**, elija *VPC and more*. Cree `coquechas-prod-vpc` con dos AZ, dos subredes públicas y cuatro privadas. Una distribución válida es:

| Capa | AZ A | AZ B | Uso |
|---|---|---|---|
| Pública | `10.20.0.0/24` | `10.20.1.0/24` | ALB y NAT Gateway. |
| Privada app | `10.20.10.0/24` | `10.20.11.0/24` | EC2/ASG. |
| Privada DB | `10.20.20.0/24` | `10.20.21.0/24` | RDS únicamente. |

Seleccione un Internet Gateway y un NAT Gateway por AZ si se busca alta disponibilidad; el mínimo académico puede usar un NAT Gateway único, documentando que no tolera su AZ. La ruta de subred pública va a Internet Gateway; las privadas de aplicación salen por NAT para actualizar el sistema, obtener imágenes ECR y consultar servicios AWS. Las privadas de DB no reciben IP pública ni ruta directa desde Internet.

**Para editar:** VPC → *Subnets* para modificar asociación de tablas de rutas; VPC → *Route tables* para rutas; VPC → *NAT gateways* para reemplazar o eliminar el NAT. No cambie el CIDR de una VPC existente: cree una nueva o un CIDR adicional si ya no alcanza.

### 4.3 Grupos de seguridad

En **EC2 → Security Groups → Create security group**, cree estas reglas sin usar rangos IP innecesarios:

| Grupo | Entrada | Salida |
|---|---|---|
| `sg-alb` | TCP 80 y 443 desde `0.0.0.0/0` (y `::/0` si habrá IPv6) | TCP 8080, 8081 y 3000 hacia `sg-app`. |
| `sg-app` | TCP 8080, 8081 y 3000 **desde `sg-alb`**; no SSH público | HTTPS 443 para AWS/actualizaciones y TCP 3306 hacia `sg-db`. |
| `sg-db` | TCP 3306 **solo desde `sg-app`** | Salida por defecto o la mínima que requiera el motor. |

Para administrar EC2 use Systems Manager Session Manager en vez de abrir el puerto 22. Si se autoriza una excepción temporal de SSH, restrínjala a una IP fija y elimínela al terminar.

**Para editar:** seleccione el grupo → pestaña **Inbound rules** o **Outbound rules** → *Edit*. El origen debe ser el ID del otro grupo, no una CIDR amplia.

### 4.4 IAM: roles, no claves permanentes

En **IAM → Roles → Create role**, cree `coquechas-prod-ec2-role` para *EC2*. Adjunte las políticas administradas necesarias para Session Manager y CloudWatch Agent, y una política propia de mínimo privilegio para ECR, Parameter Store y S3. Reemplace cuenta, región y bucket:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "ReadRuntimeConfiguration",
      "Effect": "Allow",
      "Action": ["ssm:GetParameter", "ssm:GetParametersByPath"],
      "Resource": "arn:aws:ssm:us-east-1:<ACCOUNT_ID>:parameter/coquechas/production/*"
    },
    {
      "Sid": "UseEvidenceBucket",
      "Effect": "Allow",
      "Action": ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
      "Resource": "arn:aws:s3:::coquechas-agua-evidences-production-<ACCOUNT_ID>/reports/*"
    },
    {
      "Sid": "ListEvidenceBucket",
      "Effect": "Allow",
      "Action": ["s3:ListBucket"],
      "Resource": "arn:aws:s3:::coquechas-agua-evidences-production-<ACCOUNT_ID>",
      "Condition": {"StringLike": {"s3:prefix": ["reports/*"]}}
    },
    {
      "Sid": "PullApplicationImages",
      "Effect": "Allow",
      "Action": ["ecr:GetAuthorizationToken"],
      "Resource": "*"
    },
    {
      "Sid": "ReadImagesFromProjectRepositories",
      "Effect": "Allow",
      "Action": ["ecr:BatchCheckLayerAvailability", "ecr:GetDownloadUrlForLayer", "ecr:BatchGetImage"],
      "Resource": "arn:aws:ecr:us-east-1:<ACCOUNT_ID>:repository/coquechas/*"
    }
  ]
}
```

Adjunte el rol al Launch Template/EC2 como *instance profile*. Laravel debe usar ese rol: en producción se dejan vacías `AWS_ACCESS_KEY_ID` y `AWS_SECRET_ACCESS_KEY`; el SDK obtiene credenciales temporales automáticamente. Si S3 o Parameter Store usan una CMK propia, añada también permisos KMS de descifrado a esa clave.

**Para editar:** IAM → Roles → `coquechas-prod-ec2-role` → *Permissions*. Pruebe siempre con una cuenta no raíz y revise **IAM Access Analyzer** antes de ampliar una política.

### 4.5 ECR: imágenes inmutables

En **Amazon ECR → Private repositories → Create repository**, cree los tres repositorios. Active *Tag immutability* y *Scan on push*; añada una regla de ciclo de vida que conserve, por ejemplo, las últimas 10 versiones. Publique con una etiqueta inmutable (`<git-sha>`), no con solo `latest`.

El resultado mínimo será:

```text
<account-id>.dkr.ecr.us-east-1.amazonaws.com/coquechas/laravel:<git-sha>
<account-id>.dkr.ecr.us-east-1.amazonaws.com/coquechas/react:<git-sha>
<account-id>.dkr.ecr.us-east-1.amazonaws.com/coquechas/next:<git-sha>
```

**Para editar:** ECR → repositorio → *Images* permite revisar o borrar versiones; *Lifecycle policy* cambia la retención. Nunca borre una imagen que aún esté referenciada por la versión desplegada.

### 4.6 RDS MariaDB

En **RDS → Databases → Create database** seleccione *Standard create*, motor **MariaDB**, plantilla según presupuesto, VPC `coquechas-prod-vpc` y el DB subnet group de las dos subredes privadas DB. Configure:

- Identificador: `coquechas-prod-db`; base inicial: `coquechas_agua`.
- `Public access: No`; grupo de seguridad: `sg-db`.
- Cifrado habilitado, backup automatizado (mínimo 7 días), mantenimiento en ventana definida y eliminación con snapshot final.
- Usuario maestro solo para administración; después cree usuarios independientes: Laravel escritura necesaria, Next con privilegios limitados a sus tablas/lecturas acordadas. React no recibe usuario ni contraseña de base.

Guarde endpoint, puerto 3306, usuario y contraseña en Parameter Store; no los copie en el repositorio ni en una AMI. RDS no tiene que exponerse para usar una herramienta local: entre mediante Session Manager en una EC2 o un túnel controlado.

**Para editar:** RDS → `coquechas-prod-db` → *Modify*. Los cambios marcados *Apply immediately* pueden ocasionar interrupción; los demás se aplican durante la ventana de mantenimiento. Use *Actions → Take snapshot* antes de cambios de motor, red o parámetros.

### 4.7 S3: evidencias privadas

En **S3 → Create bucket**, use el nombre global propuesto y la misma región. Mantenga **Block all public access** activado, *Bucket owner enforced* (ACLs desactivadas), cifrado por defecto SSE-S3 o KMS, Versioning activado y una regla de ciclo de vida para evidencias antiguas si el curso define retención. No habilite *Static website hosting*: este bucket no entrega frontend ni evidencia pública.

La clave Laravel debe seguir el contrato `reports/{report-id}/{uuid}-{nombre-normalizado}`. El bucket nunca necesita una política pública; el rol EC2 ya entrega acceso al prefijo `reports/`. Para descargar, Laravel valida permiso y genera URL firmada temporal.

**Para editar:** S3 → bucket → pestañas *Permissions*, *Properties*, *Management* y *Objects*. Para probar, suba un archivo con prefijo `reports/prueba/`, confirme que no abre anónimamente, y elimínelo al terminar. Modificar *Block public access* exige una justificación de seguridad y no es parte de este proyecto.

### 4.8 Parameter Store: configuración y secretos

En **Systems Manager → Parameter Store → Create parameter**, cree la jerarquía siguiente. Use `SecureString` para secretos y `String` para valores no sensibles. Esos parámetros son regionales.

| Nombre | Tipo | Ejemplo / propósito |
|---|---|---|
| `/coquechas/production/app-url` | String | `https://app.coquechas.tld` |
| `/coquechas/production/db-host` | String | endpoint privado de RDS |
| `/coquechas/production/db-database` | String | `coquechas_agua` |
| `/coquechas/production/db-username` | SecureString | usuario de aplicación |
| `/coquechas/production/db-password` | SecureString | contraseña de aplicación |
| `/coquechas/production/laravel-app-key` | SecureString | valor `base64:...` generado por Laravel |
| `/coquechas/production/session-domain` | String | `.coquechas.tld` |
| `/coquechas/production/sanctum-domains` | String | hosts autorizados separados por coma |
| `/coquechas/production/s3-bucket` | String | nombre de bucket |

En el arranque de EC2, un script con AWS CLI debe leerlos con decryption, generar un archivo `.env` con permisos `600` fuera del repositorio y arrancar Compose. La aplicación debe recibir solo los valores que necesita. Si se actualiza un secreto, actualice el parámetro, reinicie los contenedores y verifique salud; cambiar un parámetro no reinicia la aplicación por sí solo.

**Para editar:** Systems Manager → Parameter Store → parámetro → *Edit*. Mantenga historial/versión y anote en el registro de despliegue qué versión se aplicó. Para un flujo más avanzado de rotación automática, sustituya las credenciales DB por AWS Secrets Manager, pero no use ambos sin una razón operativa clara.

### 4.9 EC2, Compose y Auto Scaling

1. Cree un **Launch Template** desde EC2 con Amazon Linux 2023, tipo de instancia aprobado por presupuesto, `sg-app`, subredes privadas, disco cifrado y perfil `coquechas-prod-ec2-role`.
2. En *Advanced details → User data*, instale Docker/Compose, inicie sesión en ECR usando el rol, descargue el repositorio/artefacto de despliegue y arranque `docker compose -f docker-compose.production.yml up -d`.
3. Cree un **Auto Scaling Group** desde el Launch Template: subredes privadas de ambas AZ, capacidad deseada 1 (MVP) o 2 (balanceo), y adjunte los tres target groups.
4. Cree o adapte el Compose de producción antes de lanzar. Debe excluir `mariadb`, `moto`, `mailpit`, Vite y bind mounts. Debe publicar solo en la red privada de la instancia: Laravel `8080:80`, React/Nginx `8081:80` y Next `3000:3000`.

Variables mínimas de producción (no valores reales):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.coquechas.tld
SESSION_DOMAIN=.coquechas.tld
SANCTUM_STATEFUL_DOMAINS=app.coquechas.tld,api.coquechas.tld,operaciones.coquechas.tld,panel.coquechas.tld
DB_HOST=<endpoint-rds>
DB_DATABASE=coquechas_agua
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=coquechas-agua-evidences-production-<account-id>
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

La autenticación cross-subdomain requiere, además, que la configuración CORS de Laravel permita solo los orígenes HTTPS reales y `supports_credentials=true`. React debe consumir `https://api.coquechas.tld/api/v1`, nunca una IP ni `localhost`.

**Para editar:** EC2 → *Launch Templates* → *Actions → Modify template (Create new version)*; luego Auto Scaling → grupo → *Edit* y cambie a la versión nueva. EC2 → instancia → *Connect → Session Manager* abre administración sin SSH. Reemplazar instancias mediante ASG es preferible a editarlas manualmente.

### 4.10 ACM, ALB y target groups

1. En **Certificate Manager** (misma región del ALB) solicite certificado público para `coquechas.tld` y `*.coquechas.tld`. Valídelo por DNS en Route 53 y espere estado **Issued**.
2. En **EC2 → Target Groups**, cree tres grupos de tipo *Instances*, protocolo HTTP:

| Target group | Puerto | Health check | Éxito |
|---|---:|---|---|
| `tg-coquechas-laravel` | 8080 | `/up` | `200-399` |
| `tg-coquechas-react` | 8081 | `/` | `200-399` |
| `tg-coquechas-next` | 3000 | `/api/health` (implementar) | `200-399` |

3. En **EC2 → Load Balancers → Create Application Load Balancer**, elija Internet-facing, las dos subredes públicas y `sg-alb`.
4. Cree listener HTTP:80 cuya acción única sea redireccionar a HTTPS:443. Cree listener HTTPS:443 con el certificado ACM y reglas por host, en orden: `app.coquechas.tld` y `api.coquechas.tld` a Laravel; `operaciones.coquechas.tld` a React; `panel.coquechas.tld` a Next. El listener HTTPS no debe tener una aplicación sensible como regla predeterminada; use respuesta 404.
5. Registre instancias saludables en los tres grupos (el ASG puede hacerlo automáticamente). No continúe con DNS hasta que **Targets** muestre *Healthy*.

**Para editar:** ALB → pestaña *Listeners and rules* → seleccione HTTPS:443 → *Manage rules*. Target group → *Health checks* permite cambiar ruta/timeout; haga primero pruebas en una sola instancia. Las reglas por `host-header` son la forma prevista para dirigir cada subdominio a su aplicación.

### 4.11 Route 53

En **Route 53 → Hosted zones**, cree o abra la zona de `coquechas.tld`. Si el dominio se registró fuera de Route 53, copie los cuatro nameservers de la hosted zone al registrador. Cree registros tipo **A — Alias** (y AAAA Alias si usa IPv6), todos apuntando al ALB:

| Nombre | Destino |
|---|---|
| `app.coquechas.tld` | ALB |
| `api.coquechas.tld` | ALB |
| `operaciones.coquechas.tld` | ALB |
| `panel.coquechas.tld` | ALB |

**Para editar:** Route 53 → Hosted zone → seleccione registro → *Edit record*. DNS puede tardar en propagarse; no modifique CORS/Sanctum hasta confirmar que cada hostname responde con HTTPS válido.

### 4.12 CloudWatch, logs, alarmas y notificaciones

1. Cree grupos de logs, por ejemplo `/coquechas/production/laravel`, `/react`, `/next` y `/nginx`, con retención definida (30–90 días según el curso).
2. Configure CloudWatch Agent desde **EC2 → Instances → Actions → Monitor and troubleshoot → Configure CloudWatch agent**, o mediante User Data/SSM. El rol requiere permisos de CloudWatch Agent. Envíe logs de contenedores y métricas de disco/memoria; no escriba secretos en logs.
3. En **CloudWatch → Alarms**, cree como mínimo: `UnHealthyHostCount >= 1`, `HTTPCode_ELB_5XX_Count > 0`, utilización de CPU EC2 sostenida, espacio de disco bajo y alarma de RDS (CPU, almacenamiento libre o conexiones). Revise los umbrales con datos reales después de la primera semana.
4. En **SNS → Topics**, cree `coquechas-production-alerts`, suscriba el correo del equipo y selecciónelo como acción de cada alarma. Confirme la suscripción en el correo.

**Para editar:** CloudWatch → *Log groups* (retención), *Alarms* (umbral/acción) y *Dashboards* (métricas). SNS → topic → *Subscriptions* para cambiar destinatarios. No silencie una alarma borrándola: documente la causa y ajuste su umbral.

## 5. Despliegue y actualización segura

### Primera publicación

1. Termine Fases 5–6: evidencias S3, endpoint `/api/health` para Next, imágenes de producción y pruebas locales.
2. Construya, pruebe y publique las tres imágenes con el SHA de Git en ECR.
3. Cree parámetros, RDS, bucket, roles, red, Launch Template/ASG, target groups, ALB, certificado y DNS siguiendo el orden anterior.
4. Desde una sola instancia saludable ejecute las migraciones Laravel y los seeders permitidos para producción. Nunca ejecute `migrate` simultáneamente en cada réplica del ASG.
5. Compruebe cada target group, HTTPS, login entre subdominios, un turno, una evidencia privada S3 y el panel Next. Revise CloudWatch.

### Cambio de aplicación

1. Compile y pruebe la nueva versión; publique nuevas etiquetas ECR inmutables.
2. Cree una nueva versión del Launch Template que apunte a esos tags o actualice el parámetro de versión que consume User Data.
3. Actualice el ASG con *Instance refresh* en lotes. Espere que los tres health checks del ALB estén sanos antes de seguir.
4. Si falla, cancele el refresh y vuelva a la versión anterior del Launch Template/etiquetas; no modifique la imagen anterior.

### Cambio de configuración o secreto

1. Edite el parámetro adecuado en Parameter Store.
2. Reinicie controladamente los contenedores o reemplace las instancias para que relean el valor.
3. Valide ALB, autenticación, RDS o S3 según corresponda y deje anotada la versión aplicada.

### Cambio de esquema

1. Tome snapshot manual de RDS.
2. Despliegue código compatible con ambas versiones si el cambio no es trivial.
3. Ejecute la migración una sola vez y confirme aplicación sana antes de retirar compatibilidad anterior.

## 6. Validación de entrega

- [ ] El presupuesto y MFA están activos; no se usa el usuario raíz.
- [ ] RDS no es público y solo `sg-app` entra por 3306.
- [ ] S3 mantiene Block Public Access y las evidencias solo se acceden mediante URL firmada autorizada.
- [ ] Las EC2 no aceptan SSH ni puertos de aplicación desde Internet.
- [ ] ALB redirige HTTP a HTTPS y todos los certificados están válidos.
- [ ] Las cuatro URLs usan Route 53 y los tres target groups están saludables.
- [ ] Laravel usa rol IAM, no `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY` de producción.
- [ ] Parámetros sensibles son `SecureString` y no existen en Git, logs ni imágenes.
- [ ] Hay backup de RDS, versionado S3, logs y alarmas verificadas.
- [ ] Existe evidencia: capturas de arquitectura, ALB/targets, RDS privado, bucket privado, DNS/HTTPS, CloudWatch y recorrido funcional completo.

## 7. Referencias oficiales

- [RDS: opciones y acceso de instancias](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/USER_CreateDBInstance.Settings.html)
- [S3: bloqueo de acceso público](https://docs.aws.amazon.com/AmazonS3/latest/userguide/access-control-block-public-access.html)
- [Parameter Store: creación desde consola](https://docs.aws.amazon.com/systems-manager/latest/userguide/parameter-create-console.html)
- [ALB: reglas por host](https://docs.aws.amazon.com/elasticloadbalancing/latest/application/add-rule.html)
- [CloudWatch Agent desde consola EC2](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/install-and-configure-cloudwatch-agent-using-ec2-console.html)
