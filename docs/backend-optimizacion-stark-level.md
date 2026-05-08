
# Reporte de Optimización Backend

## Branch

`Optimizacion-Stark-Level`

## Scope

Este refactor se enfocó en mejorar la **mantenibilidad del backend**, la **validación de requests**, el **rendimiento de búsqueda** y la **escalabilidad**, sin cambiar de forma agresiva el comportamiento actual del negocio.

El principio guía fue:

* preservar las rutas actuales y las formas principales de respuesta
* reducir la complejidad de los controllers
* preparar el codebase para crecimiento futuro
* evitar reescrituras riesgosas del schema porque las migraciones del repositorio todavía no están alineadas con las tablas de negocio usadas en runtime

## What Changed

### 1. Refactor del módulo de Profile

El `ProfileController` anterior concentraba validación, file uploads, sincronización legacy, formateo de respuesta y lógica de búsqueda en múltiples tablas dentro de un solo archivo.

Esa lógica fue separada en:

* `app/Services/ProfileService.php`
* `app/Services/ProfileSearchService.php`
* `app/Services/PublicAssetUrlService.php`

**Beneficios:**

* los controllers ahora son más livianos y fáciles de leer
* los flujos de escritura de profile quedaron agrupados en un solo service
* la lógica de búsqueda de profile quedó aislada y es más fácil de optimizar
* la generación de public storage URL ahora es reutilizable en lugar de estar duplicada

### 2. Nueva capa de validación de request

Se agregaron nuevas clases `FormRequest`:

* `app/Http/Requests/RegisterRequest.php`
* `app/Http/Requests/FormacionAcademicaRequest.php`
* `app/Http/Requests/ProfileRequest.php`

**Beneficios:**

* la validación ahora es explícita y reutilizable
* los controllers ya no necesitan validar manualmente los payloads
* el contrato de la API es más estable para integraciones con frontend

### 3. Mejoras de rendimiento en búsqueda

La implementación de búsqueda de profile ya no depende de:

* consultar tablas secundarias
* `pluck(usuario_id)`
* una segunda query `whereIn(...)` sobre users

La nueva estrategia de búsqueda usa `whereHas(...)` sobre relaciones desde `Usuario`.

**Beneficios:**

* menos pasos de query
* mejor legibilidad
* indexación futura más fácil
* menos duplicación en el código de búsqueda

### 4. Resolución de usuario OAuth más segura

Se agregó:

* `app/Services/OAuthUserService.php`

Y se refactorizaron:

* `GitHubController`
* `GoogleController`
* `LinkedInController`

**Beneficios:**

* menos código duplicado en login por provider
* reutilización más segura de usuarios existentes
* mejor centralización de la emisión de tokens

### 5. Patrones de acceso a modelos más limpios

Se agregó `scopeForUser(...)` a modelos pertenecientes al usuario:

* `Proyecto`
* `Experience`
* `Social`
* `Habilidad`
* `FormacionAcademica`

**Beneficios:**

* queries más consistentes en los controllers
* futura extracción a repository/service más sencilla
* menos repetición de `where('usuario_id', ...)`

### 6. Mejor casting para entidades de dominio

Se agregaron casts para:

* `Usuario.fecha_nacimiento`
* `Usuario.perfil_completado`
* `Experience.fecha_inicio`
* `Experience.fecha_fin`
* `FormacionAcademica.fecha_inicio`
* `FormacionAcademica.fecha_fin`

**Beneficios:**

* serialización más predecible
* manejo de fechas más seguro

### 7. Endpoint agregado de profile

Se agregó:

* `GET /api/perfil/overview`

Esta ruta devuelve los datos relacionados al profile agrupados en una sola respuesta:

* profile
* skills
* experience
* projects
* socials
* formacion

**Beneficios:**

* reduce roundtrips del frontend
* mejora el rendimiento percibido para dashboards de profile
* simplifica futuros data loaders del frontend

### 8. Desacople de la URL de reset password

Se actualizó la notificación de reset password para usar:

* `config('app.frontend_url')`

en lugar de una frontend URL hardcodeada.

**Beneficios:**

* despliegue más seguro entre distintos entornos
* menos acoplamiento al entorno local

### 9. Migración aditiva de optimización de base de datos

Se agregó:

* `database/migrations/2026_04_10_000001_add_backend_optimization_indexes.php`

Esta migración agrega índices solo si:

* la tabla existe
* las columnas requeridas existen
* el índice no existe todavía

**Beneficios:**

* optimización más segura para entornos existentes
* sin forzar una reescritura del schema

## Files Added

* `app/Http/Requests/RegisterRequest.php`
* `app/Http/Requests/FormacionAcademicaRequest.php`
* `app/Http/Requests/ProfileRequest.php`
* `app/Services/PublicAssetUrlService.php`
* `app/Services/OAuthUserService.php`
* `app/Services/ProfileSearchService.php`
* `app/Services/ProfileService.php`
* `database/migrations/2026_04_10_000001_add_backend_optimization_indexes.php`
* `tests/Unit/PublicAssetUrlServiceTest.php`

## Files Refactored

* `app/Http/Controllers/AuthController.php`
* `app/Http/Controllers/FormacionAcademicaController.php`
* `app/Http/Controllers/ProfileController.php`
* `app/Http/Controllers/ExperienceController.php`
* `app/Http/Controllers/ProjectController.php`
* `app/Http/Controllers/SkillController.php`
* `app/Http/Controllers/SocialController.php`
* `app/Http/Controllers/Auth/GitHubController.php`
* `app/Http/Controllers/Auth/GoogleController.php`
* `app/Http/Controllers/Auth/LinkedInController.php`
* `app/Models/Usuario.php`
* `app/Models/Proyecto.php`
* `app/Models/Experience.php`
* `app/Models/Social.php`
* `app/Models/Habilidad.php`
* `app/Models/FormacionAcademica.php`
* `app/Notifications/ResetPasswordNotification.php`
* `routes/api.php`

## Verification

Comandos ejecutados después del refactor:

* `php artisan route:list`
* `php artisan test`

**Resultado observado:**

* las rutas cargan correctamente
* los tests pasan

## Restricciones importantes que siguen abiertas

Estas no se forzaron intencionalmente en este refactor porque requieren trabajo coordinado sobre el schema y acuerdo del equipo:

* las migraciones del repositorio todavía no están alineadas con las tablas de negocio actualmente usadas por los models
* hay migraciones duplicadas de `personal_access_tokens` en el repositorio
* el repositorio todavía no define el schema completo para `usuarios`, `habilidades`, `proyectos`, `social`, `experience`, `formacion_academica` y tablas de negocio relacionadas
* los contratos de respuesta todavía no están completamente estandarizados entre todos los módulos
* sigue habiendo casi nada de cobertura automatizada a nivel feature para los flujos de negocio

## Próxima fase recomendada

### Priority 1

* crear la fuente real de verdad de migraciones para todas las tablas de negocio
* consolidar `users` vs `usuarios`
* eliminar migraciones duplicadas u obsoletas
* documentar formalmente el schema de producción

### Priority 2

* agregar feature tests para auth, profile, projects, skills, socials y search
* agregar integration tests para profile overview
* validar el comportamiento de búsqueda con fixtures realistas

### Priority 3

* mover el formateo repetitivo de respuestas a API Resources
* estandarizar JSON envelopes entre módulos
* definir una única convención canónica de nombres para payloads

### Priority 4

* evaluar uso de queue para notifications y tareas de background más pesadas
* considerar caching en endpoints de lectura frecuente una vez que el schema y las reglas de invalidación estén estables

## Recomendaciones para el equipo

* mantener los controllers livianos y empujar las reglas de negocio hacia services
* usar `FormRequest` para cada endpoint de escritura
* evitar agregar nuevos endpoints que mezclen validación, acceso a DB y formateo dentro de un solo controller
* no introducir nuevos cambios de schema sin migraciones correspondientes en el repositorio
* preferir primero migraciones aditivas; migraciones destructivas solo después de validar los datos de producción
* al agregar consumidores frontend, favorecer la ruta agregada `perfil/overview` para pantallas de profile
* evitar orquestación directa de API a nivel página que requiera muchas llamadas secuenciales

## Forma sugerida del módulo Backend

Estructura futura recomendada:

* `app/Http/Controllers/...`
* `app/Http/Requests/...`
* `app/Http/Resources/...`
* `app/Services/Profile/...`
* `app/Services/Auth/...`
* `app/Services/Search/...`
* `app/Models/...`
* `tests/Feature/...`
* `tests/Unit/...`

El siguiente paso arquitectónico debería ser **services orientados por módulo**, no controllers más grandes.

---

Si querés, también te lo dejo en una **versión más prolija tipo informe formal**, con **títulos en negrita y numeración**, para pegar directo en Word.
