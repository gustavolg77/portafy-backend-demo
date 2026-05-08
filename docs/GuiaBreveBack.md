# Backend `portafolio`

Backend principal de PortaFy construido con `Laravel 11 + PHP 8.2`.

## Arranque rapido

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Variables locales que el equipo debe revisar primero en `.env`:

- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CACHE_STORE`
- `FRONTEND_URL`
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`
- `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`
- `LINKEDIN_CLIENT_ID`, `LINKEDIN_CLIENT_SECRET`
- `RECAPTCHA_SECRET_KEY`

## Estructura que debe respetar el equipo

- `routes/api.php`: contrato principal de la API
- `app/Http/Controllers/`: entrada HTTP
- `app/Http/Controllers/Auth/`: OAuth social
- `app/Http/Requests/`: validaciones formales
- `app/Http/Resources/`: formato estable de salida JSON
- `app/Models/`: entidades y relaciones Eloquent
- `app/Services/`: logica de negocio reutilizable
- `database/migrations/`: cambios de schema
- `tests/`: pruebas de comportamiento
- `config/`: configuracion centralizada
- `resources/views/`: solo vistas puntuales del backend, no el frontend principal
- `docs/`: guias y decisiones internas

## Que tocar segun el cambio

- Nuevo endpoint: `routes/api.php`
- Nueva validacion: `app/Http/Requests/`
- Nueva logica de negocio: `app/Services/`
- Nueva relacion o scope: `app/Models/`
- Nueva respuesta JSON estable: `app/Http/Resources/`
- Nuevo flujo social: `app/Http/Controllers/Auth/`
- Cambio de base de datos: `database/migrations/`
- Config nueva o flags: `config/`

## Reglas practicas

- El controller recibe, delega y responde.
  No debe concentrar toda la logica.
- Si un flujo toca varias tablas o varios pasos, moverlo a `app/Services/`.
- Si el payload crece, usar `FormRequest`.
- No hardcodear URLs ni secretos en controllers/services.
  Llevarlos a `.env` + `config/`.
- Antes de cambiar nombres de campos de respuesta, revisar impacto en frontend.
- Este repo no es el frontend principal.
  No montar aqui vistas del producto que pertenecen a `frontend-tis`.

## Flujo recomendado para agregar algo nuevo

1. Definir la ruta en `routes/api.php`.
2. Crear o actualizar el `FormRequest`.
3. Mantener el controller corto.
4. Mover la logica real a un service si corresponde.
5. Ajustar modelo/resource si el contrato cambia.
6. Agregar o actualizar test.

## Archivos clave

- `app/Services/AuthService.php`: login y registro
- `app/Services/ProfileService.php`: perfil y perfil profesional
- `app/Services/OAuthUserService.php`: usuarios sociales
- `app/Http/Controllers/ProfileController.php`: endpoints de perfil
- `app/Http/Requests/RegisterRequest.php`: reglas de registro
- `config/services.php`: integraciones externas

## Verificacion minima antes de subir cambios

```bash
php artisan test
php artisan optimize:clear
```

Si solo tocaste sintaxis o un archivo puntual:

```bash
php -l ruta/del/archivo.php
```

## Documentacion interna

- [docs/backend-estructura-y-guia-practica.md](docs/backend-estructura-y-guia-practica.md)
- [docs/backend-optimizacion-stark-level.md](docs/backend-optimizacion-stark-level.md)
