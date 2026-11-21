#!/usr/bin/env bash

set -Eeuo pipefail

ROOT="/opt/coquechas/coquecha_web_project"
COMPOSE_FILE="$ROOT/infrastructure/docker-compose.production.yml"
STATE_DIR="/opt/coquechas/deploy-state"
LOCK_FILE="/tmp/coquechas-deploy.lock"

declare -a SERVICES=()
declare -a HEALTH_SERVICES=()

log() {
    printf '[%s] %s\n' "$(date --iso-8601=seconds)" "$*"
}

fail() {
    log "ERROR: $*"
    exit 1
}

compose() {
    docker compose -f "$COMPOSE_FILE" "$@"
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "Falta el comando requerido: $1"
}

select_services() {
    case "$1" in
        laravel)
            SERVICES=(laravel-php laravel-nginx)
            HEALTH_SERVICES=(laravel-nginx)
            ;;
        react)
            SERVICES=(react)
            HEALTH_SERVICES=(react)
            ;;
        next)
            SERVICES=(next)
            HEALTH_SERVICES=(next)
            ;;
        all)
            SERVICES=(laravel-php laravel-nginx react next)
            HEALTH_SERVICES=(laravel-nginx react next)
            ;;
        *)
            fail "Módulo desconocido: $1. Usa all, laravel, react o next."
            ;;
    esac
}

ensure_clean_checkout() {
    if ! git diff --quiet || ! git diff --cached --quiet; then
        fail "El servidor tiene cambios rastreados sin confirmar. Revísalos antes de desplegar."
    fi
}

update_code() {
    ensure_clean_checkout
    PREVIOUS_SHA="$(git rev-parse --short=12 HEAD)"
    initialize_state "$PREVIOUS_SHA"

    log "Consultando origin/main..."
    git fetch --prune origin main
    git merge --ff-only origin/main

    IMAGE_TAG="$(git rev-parse --short=12 HEAD)"
    load_state_tags
    set_selected_tags "$IMAGE_TAG"
    log "Commit seleccionado: $IMAGE_TAG"
}

service_tag_variable() {
    case "$1" in
        laravel-php) printf 'LARAVEL_PHP_TAG' ;;
        laravel-nginx) printf 'LARAVEL_NGINX_TAG' ;;
        react) printf 'REACT_TAG' ;;
        next) printf 'NEXT_TAG' ;;
        *) return 1 ;;
    esac
}

set_service_tag() {
    local variable
    variable="$(service_tag_variable "$1")"
    printf -v "$variable" '%s' "$2"
    export "$variable"
}

initialize_state() {
    local fallback_tag="$1"
    local service file container_id image_id

    mkdir -p "$STATE_DIR"
    for service in laravel-php laravel-nginx react next; do
        file="$(state_file "$service")"
        [[ -s "$file" ]] && continue

        container_id="$(compose ps -q "$service" 2>/dev/null || true)"
        if [[ -n "$container_id" ]]; then
            image_id="$(docker inspect --format '{{.Image}}' "$container_id")"
            if docker image inspect "$image_id" >/dev/null 2>&1; then
                docker image tag "$image_id" "coquechas/$service:$fallback_tag"
                printf '%s\n' "$fallback_tag" >"$file"
                log "Registrada imagen existente de $service como $fallback_tag."
            else
                log "El contenedor de $service no conserva una imagen etiquetable; el primer despliegue no tendrá rollback para este servicio."
            fi
        fi
    done
}

load_state_tags() {
    local service file tag

    for service in laravel-php laravel-nginx react next; do
        file="$(state_file "$service")"
        tag="latest"
        [[ ! -s "$file" ]] || tag="$(<"$file")"
        set_service_tag "$service" "$tag"
    done
}

set_selected_tags() {
    local tag="$1"
    local service

    for service in "${SERVICES[@]}"; do
        set_service_tag "$service" "$tag"
    done
}

build_images() {
    log "Construyendo imágenes: ${SERVICES[*]}"
    compose build --pull "${SERVICES[@]}"
}

run_migrations() {
    if [[ " ${SERVICES[*]} " == *" laravel-php "* ]]; then
        log "Ejecutando migraciones Laravel..."
        compose run --rm --no-deps laravel-php php artisan migrate --force
    fi
}

start_services() {
    log "Actualizando contenedores..."
    compose up -d "${SERVICES[@]}"

    if [[ " ${SERVICES[*]} " == *" laravel-php "* ]]; then
        compose exec -T laravel-php php artisan config:cache
        compose exec -T laravel-php php artisan view:cache
    fi
}

wait_for_health() {
    local service="$1"
    local container_id status

    container_id="$(compose ps -q "$service")"
    [[ -n "$container_id" ]] || return 1

    for _ in $(seq 1 30); do
        status="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container_id")"
        case "$status" in
            healthy|running)
                log "$service está $status."
                return 0
                ;;
            unhealthy|exited|dead)
                log "$service terminó con estado $status."
                compose logs --tail=80 "$service" || true
                return 1
                ;;
        esac
        sleep 4
    done

    log "$service no alcanzó un estado saludable a tiempo."
    compose logs --tail=80 "$service" || true
    return 1
}

verify_http() {
    local name="$1"
    local url="$2"

    if ! curl --fail --silent --show-error --max-time 15 "$url" >/dev/null; then
        log "Falló la verificación HTTP de $name: $url"
        return 1
    fi

    log "Verificación HTTP correcta: $name"
}

verify_deployment() {
    local service

    for service in "${HEALTH_SERVICES[@]}"; do
        wait_for_health "$service" || return 1
    done

    [[ " ${HEALTH_SERVICES[*]} " != *" laravel-nginx "* ]] || verify_http Laravel http://127.0.0.1:8080/up
    [[ " ${HEALTH_SERVICES[*]} " != *" react "* ]] || verify_http React http://127.0.0.1:5175/health
    [[ " ${HEALTH_SERVICES[*]} " != *" next "* ]] || verify_http Next.js http://127.0.0.1:3000/api/health
}

state_file() {
    printf '%s/%s.tag' "$STATE_DIR" "$1"
}

record_success() {
    local service
    mkdir -p "$STATE_DIR"

    for service in "${SERVICES[@]}"; do
        printf '%s\n' "$IMAGE_TAG" >"$(state_file "$service")"
    done
}

rollback_service() {
    local service="$1"
    local tag="$2"
    local image="coquechas/$service:$tag"

    docker image inspect "$image" >/dev/null 2>&1 || {
        log "No existe la imagen $image; no se puede revertir $service."
        return 1
    }

    log "Revirtiendo $service a $tag..."
    set_service_tag "$service" "$tag"
    compose up -d --no-deps "$service"
}

rollback_selected() {
    local service tag file failed=0

    for service in "${SERVICES[@]}"; do
        file="$(state_file "$service")"
        if [[ ! -s "$file" ]]; then
            log "No hay una versión anterior registrada para $service."
            failed=1
            continue
        fi

        tag="$(<"$file")"
        rollback_service "$service" "$tag" || failed=1
    done

    return "$failed"
}

deploy() {
    update_code
    build_images
    run_migrations
    start_services

    if ! verify_deployment; then
        log "El despliegue no superó las verificaciones. Intentando rollback de imágenes..."
        rollback_selected || true
        return 1
    fi

    record_success
    compose ps
    log "Despliegue completado con el commit $IMAGE_TAG."
}

manual_rollback() {
    local target="$1"
    local tag="$2"
    local service

    select_services "$target"
    for service in "${SERVICES[@]}"; do
        rollback_service "$service" "$tag"
    done

    for service in "${HEALTH_SERVICES[@]}"; do
        wait_for_health "$service"
    done
}

main() {
    local action="${1:-all}"

    require_command git
    require_command docker
    require_command curl
    require_command flock

    mkdir -p "$STATE_DIR"
    exec 9>"$LOCK_FILE"
    flock -n 9 || fail "Ya existe otro despliegue en ejecución."

    cd "$ROOT"

    if [[ "$action" == "rollback" ]]; then
        [[ $# -eq 3 ]] || fail "Uso: $0 rollback {all|laravel|react|next} <tag-commit>"
        manual_rollback "$2" "$3"
        exit 0
    fi

    select_services "$action"
    deploy
}

main "$@"
