# Deploy demo Sprint 2 - Backend

Este repo demo esta preparado para Render usando Docker.

## Render

- Service type: `Web Service`
- Runtime: `Docker`
- Branch sugerida: `sprint-2-demo`
- Dockerfile: `Dockerfile`

## Variables principales

```env
APP_NAME=PortaFy
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://TU-BACKEND.onrender.com
FRONTEND_URL=https://TU-FRONTEND.vercel.app

DB_CONNECTION=pgsql
DB_HOST=TU-HOST-NEON
DB_PORT=5432
DB_DATABASE=TU_DB
DB_USERNAME=TU_USER
DB_PASSWORD=TU_PASSWORD
DB_SSLMODE=require

CLOUDINARY_URL=cloudinary://KEY:SECRET@CLOUD_NAME
CLOUDINARY_VERIFY_SSL=true

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://TU-BACKEND.onrender.com/api/auth/google/callback

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
LOG_CHANNEL=stderr
```

## Neon

Para demo es mejor usar una branch demo de Neon. Si se usa la misma base de datos del equipo:

- No ejecutar `migrate:fresh`.
- No borrar tablas.
- Ejecutar `php artisan migrate --force` solo si las migraciones son seguras e incrementales.
- Preferir host pooled de Neon si hay muchas conexiones.

## Comandos utiles en Render Shell

```bash
php artisan key:generate --show
php artisan migrate --force
php artisan config:clear
php artisan route:clear
```
