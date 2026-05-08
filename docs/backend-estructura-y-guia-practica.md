# Guia practica del backend

## Objetivo de este documento

Este documento explica como esta organizado **este repositorio backend** hoy y como deberia trabajar el equipo dentro de el.

La idea es que sirva para dos cosas:

1. entender rapidamente la estructura actual del proyecto
2. dar reglas simples para que el equipo agregue codigo nuevo sin desordenar el repo

Esta guia esta escrita para que la pueda seguir tanto alguien con experiencia como una persona junior.

---

## 1. Estructura actual del repo

### Vision general

Este proyecto es un backend en **Laravel 11 + PHP 8.2**.

Su responsabilidad principal es:

* autenticacion y registro
* login social con Google, GitHub y LinkedIn
* manejo de perfil profesional del usuario
* CRUD de skills, experience, projects, socials y formacion academica
* endpoints API para ser consumidos por un frontend externo

Este repo **no es el frontend principal del producto**. Aunque existen carpetas `resources/` y `views/`, aqui su uso es minimo y secundario.

### Carpeta raiz

#### `app/`

Es el corazon del backend. Aqui vive casi todo el codigo de negocio.

Subcarpetas importantes:

* `app/Http/Controllers/`
  Aqui entran las solicitudes HTTP. Un controller recibe la request, llama servicios o modelos y devuelve una respuesta.

* `app/Http/Controllers/Auth/`
  Controllers especificos para OAuth social. Manejan el flujo con GitHub, Google y LinkedIn.

* `app/Http/Requests/`
  Validaciones formales de entrada. Si una request necesita reglas claras, deberia vivir aqui.

* `app/Http/Resources/`
  Transformadores de salida para respuestas JSON. Hoy se usa poco, pero es una carpeta importante para futuro si se quiere estandarizar respuestas.

* `app/Models/`
  Modelos Eloquent. Representan tablas y relaciones de la base de datos.

* `app/Services/`
  Logica de negocio reutilizable. Esta carpeta es clave para evitar controllers gigantes.

* `app/Notifications/`
  Notificaciones como reset de password.

* `app/Providers/`
  Configuracion del framework, bindings y arranque de servicios.

#### `bootstrap/`

Inicializacion del framework. Normalmente casi no se toca en desarrollo diario.

#### `config/`

Archivos de configuracion de Laravel y del proyecto.

Ejemplos:

* `config/app.php`
* `config/database.php`
* `config/sanctum.php`
* `config/services.php`

Aqui deben ir valores y comportamiento configurable. No se debe hardcodear configuracion en controllers o services si puede vivir aqui.

#### `database/`

Contiene todo lo relacionado con base de datos.

Subcarpetas:

* `database/migrations/`
  Cambios versionados del schema.

* `database/seeders/`
  Datos de apoyo o carga inicial.

* `database/factories/`
  Factories para tests o generacion de datos.

#### `docs/`

Documentacion interna del equipo.

Hoy contiene:

* `backend-optimizacion-stark-level.md`
  Informe tecnico de la optimizacion backend aplicada.

* `backend-estructura-y-guia-practica.md`
  Esta guia operativa del repositorio.

#### `public/`

Punto de entrada publico del proyecto. Laravel expone la app desde aqui.

#### `resources/`

Recursos secundarios del proyecto.

En este repo backend, su uso actual es limitado:

* `resources/views/`
  Vistas Blade pequenas para callbacks OAuth y la vista base `welcome`.

* `resources/js/`
  Archivo base del stack por defecto de Laravel. No es el frontend principal del sistema.

* `resources/css/`
  CSS base del stack por defecto.

Regla importante: **no usar `resources/` para construir el frontend completo del producto**. Ese trabajo debe vivir en su repo frontend.

#### `routes/`

Define las rutas del proyecto.

* `routes/api.php`
  Rutas principales del backend para frontend y clientes externos.

* `routes/web.php`
  Rutas web basicas de Laravel.

* `routes/console.php`
  Comandos programados o logica de consola.

#### `storage/`

Archivos generados por la app, logs, cache y archivos subidos si aplica.

#### `tests/`

Pruebas automatizadas.

* `tests/Feature/`
  Tests que validan comportamiento funcional de endpoints o flujos.

* `tests/Unit/`
  Tests pequenos de clases o servicios aislados.

#### `vendor/`

Dependencias PHP instaladas por Composer. No se edita manualmente.

#### Archivos raiz importantes

* `artisan`
  CLI de Laravel.

* `composer.json`
  Dependencias PHP y configuracion de autoload.

* `package.json`
  Dependencias frontend del stack base de Laravel. En este repo no es la capa principal del producto.

* `.env`
  Variables locales del entorno.

* `.env.example`
  Plantilla para nuevos entornos.

* `vite.config.js`
  Configuracion del bundler de assets de Laravel.

* `README.md`
  Documentacion general del proyecto.

---

## 2. Explicacion practica de cada carpeta del backend

### `app/Http/Controllers/`

Piensalo como la capa de entrada.

Que deberia hacer un controller:

* recibir la request
* validar con `FormRequest` si corresponde
* llamar a un service o modelo
* devolver respuesta JSON

Que no deberia hacer:

* contener toda la logica de negocio
* mezclar queries muy complejas, validacion, uploads y transformacion en un solo metodo
* construir procesos largos paso a paso si ese flujo puede vivir en un service

Regla del equipo:

* si un controller empieza a crecer mucho o repite logica, esa logica debe moverse a `app/Services/`

### `app/Http/Controllers/Auth/`

Esta carpeta existe para separar el login social del resto.

Aqui estan:

* `GitHubController.php`
* `GoogleController.php`
* `LinkedInController.php`

Regla del equipo:

* si se agrega un nuevo provider social, crear su controller aqui
* la logica comun no debe duplicarse; debe vivir en un service como `OAuthUserService`

### `app/Http/Requests/`

Aqui se definen reglas de validacion por caso de uso.

Ejemplos actuales:

* `RegisterRequest.php`
* `ProfileRequest.php`
* `ProjectRequest.php`
* `SkillRequest.php`

Cuando crear un `FormRequest`:

* cuando un endpoint recibe datos estructurados
* cuando las reglas son reutilizables
* cuando quieres mantener el controller limpio

Cuando no basta con validar inline:

* cuando la validacion tiene varias reglas
* cuando varios endpoints se parecen
* cuando el payload forma parte del contrato con frontend

### `app/Http/Resources/`

Sirve para transformar modelos a JSON de forma consistente.

Hoy existe `UsuarioResource.php`.

Recomendacion del equipo:

* si la API empieza a tener respuestas inconsistentes, esta carpeta debe tomar mas protagonismo
* usar Resources ayuda a no devolver campos internos por accidente

### `app/Models/`

Representan entidades de dominio y tablas de base de datos.

Ejemplos:

* `Usuario`
* `Proyecto`
* `Experience`
* `Social`
* `Habilidad`
* `FormacionAcademica`

Aqui deben vivir:

* relaciones Eloquent
* `fillable`
* `casts`
* scopes reutilizables como `forUser(...)`

Aqui no deberian vivir:

* flujos largos de negocio
* llamadas HTTP
* logica de coordinacion entre varias entidades

### `app/Services/`

Esta carpeta es una de las mas importantes del repo.

Se usa para guardar logica de negocio reutilizable y mantener controllers livianos.

Servicios actuales:

* `AuthService.php`
  Registro, login y emision de token.

* `PasswordResetService.php`
  Flujo de recuperacion y reseteo de password.

* `OAuthUserService.php`
  Resolucion y sincronizacion de usuarios OAuth.

* `ProfileService.php`
  Escritura y armado principal del perfil.

* `ProfileSearchService.php`
  Busqueda y consultas relacionadas al perfil.

* `PublicAssetUrlService.php`
  Generacion consistente de URLs publicas para assets.

Regla del equipo:

* si una logica puede ser usada por mas de un controller, probablemente debe vivir aqui
* si una operacion toca varias tablas o tiene varios pasos, mejor moverla aqui

### `app/Notifications/`

Aqui van mensajes enviados al usuario por email u otros canales.

Ejemplo actual:

* `ResetPasswordNotification.php`

Regla del equipo:

* evitar hardcodear URLs dentro de notificaciones
* usar configuracion y variables de entorno

### `config/`

La configuracion vive aqui, no en controllers.

Usar esta carpeta para:

* URLs base
* llaves de servicios externos
* toggles de comportamiento
* opciones del sistema

No hacer:

* poner strings de entorno directamente dentro del codigo de negocio

### `database/migrations/`

Cada cambio de schema debe quedar versionado.

Buenas practicas:

* una migracion por cambio claro
* nombres descriptivos
* agregar indices cuando un campo se consulta mucho
* revisar si una tabla o columna ya existe antes de hacer migraciones defensivas sobre entornos viejos

No hacer:

* editar migraciones antiguas ya usadas en otros entornos
* duplicar tablas o indices sin revisar el historial

### `routes/api.php`

Es el contrato principal del backend.

Aqui hoy viven endpoints de:

* auth
* password reset
* formacion
* skills
* experience
* projects
* socials
* perfil
* busqueda de usuario

Regla del equipo:

* si una ruta es de API, normalmente debe vivir aqui
* mantener nombres consistentes
* no agregar rutas duplicadas para el mismo caso si no hay una razon clara

### `resources/views/`

En este repo solo se usan de forma ligera.

Hoy existen:

* `welcome.blade.php`
* `github-callback.blade.php`
* `google-callback.blade.php`
* `linkedin-callback.blade.php`

Recomendacion:

* usarlas solo para lo minimo necesario del backend web
* no convertir esta carpeta en una app frontend grande

### `tests/`

Todo cambio importante del backend deberia venir con pruebas cuando sea viable.

Tipos:

* `Feature`
  Para validar endpoints, auth, permisos y respuestas.

* `Unit`
  Para validar servicios y logica pura.

Regla del equipo:

* cuando se agrega un service, pensar al menos en un unit test
* cuando se agrega o cambia un endpoint, pensar en un feature test

---

## 3. Guia para el equipo: donde agregar codigo y donde no

### Si vas a crear un endpoint nuevo

Orden recomendado:

1. definir la ruta en `routes/api.php`
2. crear o actualizar un `FormRequest` en `app/Http/Requests/`
3. crear o actualizar el controller
4. si hay logica de negocio real, moverla a `app/Services/`
5. si hace falta, actualizar modelos o resources
6. agregar test

### Si vas a agregar validacion nueva

Ponla en `app/Http/Requests/`, no dentro del controller salvo que sea algo muy pequeno.

### Si vas a agregar logica de negocio

Ponla en `app/Services/` si:

* toca varias entidades
* tiene varios pasos
* puede reutilizarse
* hace mas pesado al controller

### Si vas a agregar consultas reutilizables

Primero pregunta:

* si es una restriccion simple por modelo, ponerla como scope en el model
* si es una consulta compleja de un flujo, ponerla en un service

### Si vas a agregar transformacion de respuesta JSON

Considera usar `app/Http/Resources/` si:

* la respuesta se repite
* quieres ocultar campos internos
* el frontend necesita un formato estable

### Si vas a tocar vistas Blade

Hazlo solo si es una necesidad real del backend web o del flujo OAuth.

No usar esta carpeta para:

* construir paginas del producto que viven en el frontend real
* meter logica grande de UI
* copiar componentes de otro repo frontend

### Si vas a tocar assets en `resources/js` o `resources/css`

Hazlo solo para soporte minimo del backend.

No usar esta area para:

* montar una SPA completa
* mover aqui el desarrollo del frontend principal

---

## 4. Reglas simples para mantener orden

### Regla 1: controllers cortos

Si un controller ya esta haciendo demasiadas cosas, se parte.

Senales de alerta:

* metodos muy largos
* varios `DB::table(...)` mezclados
* validacion manual repetida
* transformacion larga de respuesta
* logica de archivos, busqueda y actualizacion en un mismo metodo

### Regla 2: contratos estables

No cambiar nombres de campos en requests o responses sin revisar impacto en frontend.

Si el equipo acuerda renombrar algo:

* documentarlo
* actualizar tests
* comunicarlo al frontend

### Regla 3: una sola responsabilidad por carpeta

* controllers reciben y devuelven
* requests validan
* services orquestan negocio
* models representan datos y relaciones
* resources formatean salida
* tests verifican comportamiento

### Regla 4: no hardcodear configuracion

Si algo depende del entorno:

* usar `.env`
* leer desde `config/`

Ejemplos:

* URLs frontend
* credenciales OAuth
* base URLs
* nombres de dominio

### Regla 5: no duplicar logica

Antes de escribir codigo nuevo, revisar si ya existe algo parecido en:

* `app/Services/`
* `app/Http/Requests/`
* `app/Models/`

Si ya existe una solucion cercana, reutilizarla o extenderla.

### Regla 6: probar antes de cerrar

Minimo recomendado antes de entregar:

* `php artisan test`
* revisar rutas si se agregaron endpoints
* revisar que no se haya roto auth o middleware

---

## 5. Recomendaciones practicas para trabajo futuro

### Recomendacion A: seguir empujando logica hacia services

El backend ya empezo ese camino y conviene mantenerlo.

Especialmente en:

* perfil
* busquedas
* auth social
* flujos con uploads o sincronizacion entre varias tablas

### Recomendacion B: usar mas `FormRequest`

Eso ayuda a:

* claridad
* orden
* contratos mas estables
* controllers mas pequenos

### Recomendacion C: fortalecer tests de dominio

Hoy hay muy poca cobertura real.

Prioridad sugerida:

1. auth y register
2. perfil
3. projects
4. skills
5. search

### Recomendacion D: documentar decisiones importantes

Si el equipo cambia:

* nombres de campos
* estructura de respuestas
* convenciones de carpetas
* flujos de auth

debe dejarlo documentado en `docs/`.

### Recomendacion E: revisar y consolidar schema

Este sigue siendo el punto estructural mas delicado del repo.

El equipo deberia planificar una fase especifica para:

* alinear migraciones con tablas reales
* eliminar duplicados
* formalizar indices y llaves foraneas

---

## 6. Que no deberia hacer el equipo en este repo

* no montar aqui el frontend principal del producto
* no meter estilos, componentes visuales o arquitectura SPA dentro del backend
* no dejar controllers gigantes
* no validar todo inline si el payload ya merece un `FormRequest`
* no duplicar logica OAuth por provider
* no hardcodear URLs de frontend o entorno
* no editar `vendor/`
* no usar `resources/` como cajon de sastre para cualquier cosa

---

## 7. Resumen rapido para una persona junior

Si eres nuevo en este repo, piensa asi:

* `routes/api.php` dice que endpoints existen
* `Controllers` reciben la request y llaman la logica
* `Requests` validan lo que entra
* `Services` hacen el trabajo importante
* `Models` hablan con la base de datos
* `Resources` pueden ordenar la salida JSON
* `Notifications` envian mensajes
* `Migrations` cambian la base de datos
* `Tests` comprueban que no rompimos nada

Regla de oro:

**si no sabes donde poner algo, no lo metas directo en el controller por reflejo. Primero piensa si es validacion, logica de negocio, acceso a datos o formateo de respuesta.**

---

## 8. Checklist corto antes de subir cambios

* La ruta esta en el archivo correcto
* La validacion esta en un `FormRequest` si aplica
* El controller sigue legible
* La logica pesada esta en un service
* No se duplico codigo existente
* No se hardcodearon URLs o secretos
* Hay al menos una prueba o una razon clara de por que aun no se agrego
* Se actualizo `docs/` si el cambio modifica una convencion importante
