# Despliegue en VPS

```bash
sudo git clone <URL_DEL_REPOSITORIO> /opt/edutech-admin
cd /opt/edutech-admin
cp .env.production.example .env.production
chmod 600 .env.production
```

Edite `.env.production`: conserve `APP_ENV=production`, `APP_DEBUG=false` y `APP_URL=https://edutechadmin.opcionapps.com`; defina contraseñas diferentes y seguras para PostgreSQL y Redis. Genere `APP_KEY` sin iniciar la aplicación:

```bash
docker run --rm php:8.4-cli-alpine php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Copie el resultado a `APP_KEY` y despliegue:

```bash
cd /opt/edutech-admin
docker compose --env-file .env.production -f compose.production.yaml build --pull
docker compose --env-file .env.production -f compose.production.yaml up -d
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan migrate --force
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan storage:link
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan optimize
docker compose --env-file .env.production -f compose.production.yaml ps
curl -fsS http://127.0.0.1:8092/up
```

No ejecute seeders en una base con datos. En una instalación inicial vacía, y solo si se requiere crear la organización, sede y usuario inicial, ejecute `docker compose --env-file .env.production -f compose.production.yaml exec app php artisan db:seed --force` y cambie inmediatamente esas credenciales.

Caddyfile del host:

```caddyfile
edutechadmin.opcionapps.com {
    reverse_proxy 127.0.0.1:8092
}
```

Valide y recargue Caddy con el mecanismo del VPS y compruebe `curl -fsS https://edutechadmin.opcionapps.com/up`. Nginx solo publica `127.0.0.1:8092`; PostgreSQL y Redis no publican puertos. Los Excel quedan excluidos de la imagen y las importaciones se guardan fuera de `public/`.
