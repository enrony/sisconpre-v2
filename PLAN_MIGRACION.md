# Plan de trabajo — Migración `prestamos_16` → `sisconpre-v2` (`prestamos_gilen`)

> **Origen:** `C:\Docker\prestamos_16` — repo `github.com/enrony/sisconpre.git` (`issue02`, tag `pre-migracion-l13`), Laravel 8 + Jetstream/Inertia/Vue 2 + webpack.
> **Destino:** `C:\Users\enron\Herd\prestamos_gilen` — repo nuevo `github.com/enrony/sisconpre-v2`, Laravel 13, entorno local Laravel Herd.
> **Producción:** VPS Ferozo de GILEN SOFT (CentOS 7 + LiteSpeed, sin Docker), mismo servidor que `crm.gilensoft.com`.
> **Fecha del plan:** 2026-09-05 · rev. 2 (tras validar repo, dump y servidor).

---

## 1. Resumen ejecutivo

Se reconstruye el proyecto sobre un esqueleto limpio de **Laravel 13 + PHP 8.3 + Vite + Vue 3 + Inertia 3**,
**alineado al stack de casa** que ya usa `crm_gilen` (Fortify + starter kit Vue, sin Jetstream; Wayfinder en vez
de Ziggy; Pest + Pint + Larastan). Se **portan pantalla por pantalla** los ~25 módulos de frontend de Vue 2 a
Vue 3, **manteniendo `element-plus`** como librería de componentes de las pantallas de negocio (decisión de
velocidad; convergencia futura a reka-ui queda como trabajo posterior).

El RBAC propio (`modules`/`profiles`) se **consolida en `spatie/laravel-permission`** y se añade autorización
real en el servidor (hoy no existe). Los datos vienen del **dump `prestamos_db.sql`**; el esquema se rehace
limpio y se importan los datos.

Despliegue **nativo** en el VPS Ferozo replicando el runbook real de `crm_gilen/docs/despliegue.md`
(`git pull` + `composer83 --no-scripts` + `migrate --force`, **`public/build/` versionado** porque el server
no tiene Node usable).

Esfuerzo estimado: **6–10 semanas** con 1 desarrollador, dominado por la Fase 6 (migración de pantallas).

---

## 2. Decisiones tomadas

| Tema            | Decisión                                                                               | Implicación                                                                                                                                                               |
| --------------- | -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Repositorio     | **Repo nuevo `enrony/sisconpre-v2`** (creado, vacío, validado ✅)                      | `sisconpre` se archiva como referencia; se migran los datos, no el historial git                                                                                          |
| Framework / PHP | **Laravel 13 + PHP 8.3**                                                               | PHP 8.3 = `lsphp83` del servidor Ferozo y de `crm_gilen`. No 8.4 (no disponible en el server)                                                                             |
| Auth / Starter  | **Fortify + starter kit Vue de Laravel 13 (sin Jetstream)**                            | Alineado con `crm_gilen`. Jetstream Teams en préstamos está vestigial (0 usos); se descartan `teams`/`team_user`. 2FA vía Fortify; API tokens vía Sanctum solo si se usan |
| Frontend        | **Portar** cada SFC Vue 2 → Vue 3 manteniendo **`element-plus`**                       | Migración mecánica por módulo; menor riesgo funcional. Coexiste con reka-ui del shell (aceptado; convergencia futura)                                                     |
| Build / assets  | **Vite**; `public/build/` **se commitea**                                              | El server no puede correr `npm run build` (Node 16). Node es dependencia **solo local**                                                                                   |
| Rutas tipadas   | **Laravel Wayfinder** (no Ziggy)                                                       | Igual que `crm_gilen`                                                                                                                                                     |
| Base de datos   | Esquema **nuevo y limpio** + **import de datos** desde `prestamos_db.sql`              | El server corre **MySQL 5.7.44** (el CRM ya valida Laravel 13 sobre 5.7). Convertir `utf8mb3` → `utf8mb4`                                                                 |
| RBAC            | **Consolidar `modules`/`profiles` en `spatie/laravel-permission`** + autorización real | Ver §11                                                                                                                                                                   |
| Despliegue      | **Nativo en VPS Ferozo** (LiteSpeed + `lsphp83`), **sin Docker**                       | Replica `crm_gilen/docs/despliegue.md`. Ver §12                                                                                                                           |

---

## 3. Stack objetivo (versiones concretas)

| Componente    | Versión / elección                                     | Nota                                                              |
| ------------- | ------------------------------------------------------ | ----------------------------------------------------------------- |
| Laravel       | `laravel/framework ^13.17`                             | igual que `crm_gilen`                                             |
| PHP           | **8.3**                                                | `/usr/local/lsws/lsphp83/bin/php` en el server; Herd 8.3 en local |
| Entorno local | Laravel Herd                                           | `prestamos_gilen.test`                                            |
| Auth          | `laravel/fortify`                                      | 2FA, reset, registro. **Sin Jetstream**                           |
| Starter kit   | Starter kit **Vue** de Laravel 13 (Inertia 3)          | shell, layout, páginas de auth                                    |
| Rutas en JS   | `laravel/wayfinder` + `@laravel/vite-plugin-wayfinder` | reemplaza `ziggy`                                                 |
| Inertia       | `inertiajs/inertia-laravel ^3` + `@inertiajs/vue3 ^3`  |                                                                   |
| Node          | **24 LTS** — **solo entorno local**                    | el server no ejecuta Node                                         |
| Bundler       | Vite (`laravel-vite-plugin`) + `@vitejs/plugin-vue`    |                                                                   |
| JS framework  | Vue `^3.5`                                             |                                                                   |
| Estado        | Pinia (ya presente, se mantiene)                       |                                                                   |
| UI negocio    | `element-plus`                                         | pantallas portadas                                                |
| UI shell      | Tailwind 4 + reka-ui (del starter kit)                 | auth, layout                                                      |
| Permisos      | `spatie/laravel-permission ^6`                         | ver §11                                                           |
| API auth      | `laravel/sanctum ^4`                                   | solo si el módulo API sigue vivo                                  |
| Fechas        | `dayjs` (reemplaza `moment`)                           |                                                                   |
| Tests         | **Pest** (`pestphp/pest`, `phpunit ^12`)               |                                                                   |
| Calidad       | `laravel/pint`, `larastan/larastan`                    | igual que `crm_gilen`                                             |
| DB (server)   | MySQL **5.7.44**                                       | evitar CTE / window functions / functional indexes en SQL crudo   |

---

## 4. Estrategia de repositorio

**Repo nuevo, independiente: `github.com/enrony/sisconpre-v2`** (creado y vacío — validado con `git ls-remote`).

1. En `sisconpre` (legado): **✅ HECHO (2026-09-05)** — commit `967c8c5` en `issue02` + tag `pre-migracion-l13`, ambos pusheados. Tras el cutover: archivar el repo (Settings → Archive).
2. En `prestamos_gilen`: `git init`, `git remote add origin https://github.com/enrony/sisconpre-v2.git`, primer commit con el scaffold de Laravel 13. `public/build/` **fuera de `.gitignore`** (se versiona).
3. Clonar `sisconpre` en carpeta hermana (`C:\Users\enron\Herd\_ref\sisconpre`) para portar lógica lado a lado; usar la rama `issue02`.
4. Repo **privado** → para el server: PAT classic con scopes `repo` + `workflow`, guardado con `git config credential.helper store` (igual que `crm_gilen`).

**No se hace:** merge del código nuevo dentro de `sisconpre`, ni rama `v2` sobre ese repo.

---

## 5. Estrategia de base de datos

Fuente de datos: **`C:\Users\enron\Downloads\respaldo_db.sql\prestamos_db.sql`** (9.7 MB, MySQL 8.0.30, HeidiSQL).
Contenido: **57 tablas**, charset **`utf8mb3`**, estado **~mayo 2025** (última migración aplicada `2025_05_23`; el
repo tiene ~13 migraciones posteriores — se incorporan al portar). Conviven `spatie` (`roles`, `permissions`,
`model_has_*`) y el RBAC propio.

1. **Esquema nuevo y limpio** en el proyecto Laravel 13:
    - Portar las ~73 migraciones del repo `issue02`, actualizadas a la API de L13 (clases anónimas, `Blueprint`).
    - Consolidar en un **baseline** con `php artisan schema:dump` una vez validado contra copia local.
    - **`utf8mb4`** (`utf8mb4_unicode_ci`) en todo el esquema nuevo. Cuidar el límite de 767 bytes en índices
      sobre `varchar` largos en MySQL 5.7 (`innodb_large_prefix` está ON por defecto en 5.7.7+).
    - Columnas monetarias y de tasa en **`DECIMAL`** (verificar que ninguna quede `float`/`double`).
    - `timezone` por país (`countries.timezone` ya existe).
2. **Import de datos** desde el dump:
    - Script de carga: mapear tabla por tabla del dump al esquema nuevo (nombres iguales salvo ajustes).
    - **Excluir basura**: filas de prueba en `actions` (`13 Prueba`, `14 Pruba 2`) y perfiles/pruebas de test.
    - Regenerar `roles`/`permissions`/`model_has_roles` desde el RBAC propio (§11), **no** copiar las tablas spatie tal cual.
    - **Descartar** `teams`, `team_user` (Jetstream vestigial).
3. **Datos maestros** por seeder idempotente (para entornos nuevos): `actions` (12 reales), `countries`,
   `country_holidays`, `frecuencias`, `tipo_prestamos`, `payment_methods`/`payment_forms`,
   `banks`/`bank_account_types`, `tipos_documentos`, `monedas`, `menu_items` (§11).
4. **Cutover**: exportar dump fresco de la BD viva → cargar en la BD del subdominio nuevo en Ferozo →
   `migrate --force` de deltas → smoke test. Backup completo antes. Rollback = repuntar document root al viejo.

---

## 6. Fases

### Fase 0 — Arranque · 0.5–1 día

- [x] Repo `sisconpre-v2` creado y validado.
- [x] Dump recibido y analizado.
- [x] Servidor identificado (VPS Ferozo, ver §12).
- [x] Commit + tag en `sisconpre`.
- [x] Subdominio de producción: **`prestamos.gilensoft.com`**, document root `prestamos_app/public`.
- [x] Panel Ferozo: BD **`gilen_prestamos`** + usuario creados. Carpeta `~/public_html/prestamos_app` creada (vacía).
- [ ] Panel Ferozo: **SSL Let's Encrypt + redirección https** para el subdominio (pendiente).
- [ ] PAT de GitHub (classic, `repo`+`workflow`) para el clone en el server (pendiente).
- [ ] Inventario funcional: marcar cada módulo/pantalla como _portar / rehacer / descartar_ (ver §8).

### Fase 1 — Scaffold base · 1–2 días — **✅ COMPLETADA (local); falta push**

- [x] Sitio Herd `prestamos_gilen.test` **aislado a PHP 8.3.33** (`herd isolate 8.3`).
- [x] Scaffold desde el branch `main` de `laravel/vue-starter-kit` (el tag estable era Laravel 12) → **Laravel 13.30.1**, Inertia 3, Fortify (registro/reset/verificación email/2FA/passkeys), Wayfinder, Tailwind 4, reka-ui, Vite (vite-plus). Trae Pint + PHPUnit + Larastan del starter kit.
- [x] `composer require spatie/laravel-permission` (v8) — `config/permission.php` + migración publicadas y ejecutadas.
- [x] `npm i element-plus @element-plus/icons-vue pinia dayjs`.
- [x] **Node 24.20.0** instalado vía nvm (`vite-plus` exige ≥22.18/24). `.nvmrc` = `24`.
- [x] `.env` local → MySQL 8 `prestamos_gilen` (root, sin clave); `APP_KEY` generado; `migrate` OK.
- [x] `.gitignore`: `/public/build` **des-ignorado** (+ `/.claude`, sqlite) — mirror de `crm_gilen`. `.env.production.example` creado (Ferozo).
- [x] `npm run build` OK → `public/build/` versionado.
- [x] Commit local `957fd15` (340 archivos) en rama `main`, remoto `origin` = `https://github.com/enrony/sisconpre-v2.git`.
- [ ] **`git push -u origin main`** — bloqueado por el clasificador de seguridad de la sesión; lo ejecuta el usuario.
- [ ] Pendiente menor: `sanctum` y `pestphp/pest` se añaden cuando se necesiten (API en Fase 4 / tests en Fase 7).

### Fase 2 — Base de datos · 2–3 días — **🟡 EN CURSO (esquema hecho)**

- [x] Esquema portado a L13: dump importado a BD scratch `prestamos_ref`; migraciones reverse-engineered con `kitloong/laravel-migrations-generator` (paquete ya removido); ajustadas (sin `connection()`, sin `no action`). **86 migraciones**, `migrate:fresh` en verde sobre MySQL 8 / `utf8mb4`.
- [x] Tablas excluidas: `users`/`password_resets`/`sessions`/`failed_jobs`/`personal_access_tokens` (starter kit), `teams`/`team_user` (Jetstream vestigial), spatie (ya publicadas).
- [x] Verificación estructural ref vs new: **las 53 tablas de negocio coinciden columna a columna**. Únicas diferencias: `users` (columnas de Jetstream teams/photo quitadas, `two_factor_confirmed_at` añadida por el starter kit) y 5 columnas `double(10,2)` → `double` (stats de países + `configuration_items.valor_numerico` — revisar si conviene `decimal` en Fase 3).
- [x] `DECIMAL` de montos/tasas verificados fieles (`decimal(15,2)`, `decimal(16,3)`, `decimal(4,2)`).
- [x] **Seeder de maestros** `LegacyCatalogSeeder` + fixtures `database/seeders/data/*.sql` (18 tablas: `countries` 239, `cities` 4095, `departments` 1417, `banks` 146, `country_holidays` 132, frecuencias/tipos/monedas/acciones/estatus…). `actions` sin las filas de prueba 13/14. Idempotente (borra y recarga). `migrate:fresh --seed` en verde, 0 huérfanos de FK.
- [ ] `menu_items` (navegación) — se genera en Fase 3 §11 junto con el RBAC.
- [ ] `php artisan schema:dump --prune` → baseline (tras validar import de datos y ajustes de Fase 3).
- [x] **Import de datos de tenant**: comando `php artisan legacy:import-data` (conexión `mysql_ref` → `prestamos_ref` cargada desde el dump). Copia server-side (`INSERT…SELECT`, FKs off), idempotente, intersección de columnas (descarta `current_team_id`/`profile_photo_path` de `users`). **27 tablas, 1463 filas**: users 2, grupos_trabajos(_users) 3/2, clientes 5, prestamos 60, prestamos_dias 851, payment_reports 16 (+methods/movements/…), + RBAC propio (modules 28, modules_actions 217, profiles 8, modules_actions_profiles 39). Excluye perfil "Prueba" y sus 11 asignaciones. **0 huérfanos de FK**, charset UTF-8 correcto.

### Fase 3 — Dominio (modelos + servicios) · 3–5 días — **🟡 EN CURSO**

- [x] **Modelos portados** (45): copiados del legado + `pint`. `User` fusionado con el starter kit (spatie `HasRoles` + relaciones `profiles`/`ownedUserCountry`/`ownedGruposTrabajoUser`; sin Jetstream ni Sanctum). Borrados `Team`/`Membership`/`State` (muertos). Arreglos: `ModulesRelation` declaraba `class Modules` (colisión); `Prestamos::datoCliente/country` eran `static` con `Self::belongsTo` (roto en PHP 8.3) → método de instancia + orden de args corregido; scopes `static` → instancia. Copiados `app/Http/Traits/{generalsTrait,permissionsTrait}`. Todos cargan y las relaciones clave resuelven.
- [ ] Revisar smells legado en Fase 4: `PaymentReport::scopeMovimientos()` sin `$query`, `$appends` con accessors que tocan relaciones, `$array_estatus` como propiedad.
- [x] **Servicios y soporte portados**: `app/Services/PaymentReportService.php` (285 líneas, motor de informes de pago); comandos `holiday:cron`, `prestamos:aplicar-recargos`, `prestamos:notificar-recargos`; `app/Events/EnviarCorreo` + `app/Listeners/EnviaCorreoUsuario` (auto-discovery OK) + `app/Mail/RecargoMail`. Todos pasados por `pint`.
- [x] **Scheduler** portado a `routes/console.php` (`schedule:list` correcto). `ScheduledTaskLog` ya como modelo.
- [x] **Descartado del legado**: `app/Actions/Jetstream/*` (sin Jetstream); `app/Actions/Fortify/*` (el starter kit ya trae los suyos); `app/Http/Middleware/*` (los 8 eran default de Laravel, ahora en el framework); `app/Policies/*` (22 eran stubs vacíos — la autorización real va por `permission:` de spatie en §11/Fase 4); `AppServiceProvider`/`AuthServiceProvider`/`EventServiceProvider` del legado (vacíos o solo mapeaban `TeamPolicy`); `app/View/Components/GuestLayout` (era Blade, ahora Inertia).
- [ ] `bootstrap/providers.php` / CORS: sin cambios necesarios (el starter kit L13 ya trae CORS nativo; `fideloper/proxy` y `fruitcake/laravel-cors` no existían que porten).
- [x] **RBAC consolidado en spatie (§11):** comando `php artisan rbac:sync-from-legacy` (idempotente) — lee `modules`/`actions`/`modules_actions`/`modules_actions_profiles`/`profiles`/`users_profiles` importados y genera **196 permisos** (`<clave>.<habilidad>`), **9 roles** (`super-admin` + 8 perfiles), concesiones (`Acceso Total` → todos los permisos del módulo; perfil `admin` → todos), y asigna roles a usuarios (perfil `su` → también `super-admin`). Helper `App\Support\Permission` (slugs). `Gate::before` para `super-admin` en `AppServiceProvider`. Tabla **`menu_items`** (nueva migración) + modelo + `App\Support\Menu` que arma el árbol filtrado por `can(permission)`. `HandleInertiaRequests` comparte `auth.roles`, `auth.permissions` y `menu`. Verificado: super pasa todo; "Cliente Verficado" ve 9 permisos y menú reducido. pint + larastan L5 OK.
- [ ] **Orden de arranque de datos** (documentar): `migrate --seed` (catálogos) → `legacy:import-data` (tenant) → `rbac:sync-from-legacy` (spatie + menú).

### Fase 4 — Rutas y controladores · 3–5 días — **🟡 backend funcional**

- [x] **24 archivos de rutas** portados a `routes/` y registrados en `bootstrap/app.php` (`then:`). `auth:sanctum` → `auth`; se elimina el middleware propio `access_enrony` (`AccessVerified`, era el chequeo de RBAC casero).
- [x] **Cada archivo de módulo protegido con `permission:<clave>.listar`** — sin ese permiso no se accede a ninguna acción del módulo. Ajuste fino por acción (`registrar`/`editar`/`eliminar`) → Fase 6/9.
- [x] Alias de middleware de spatie (`role`/`permission`/`role_or_permission`) registrados en `bootstrap/app.php` (v6 no lo hace solo).
- [x] **39 controladores + 50 FormRequests** portados + `pint`. Base `Controller` fusionado con el del starter kit (helpers de tenant `obtenerGrupoTrabajo*` + `generalsTrait`). Locale de la app → `es`.
- [x] `routes/api.php` con utilidades (`/listaTipoDocu`, `/dateServerCurrent`). Sanctum sigue diferido (no hay API con tokens).
- [x] **Smoke test** (`tests/Feature/PanelSmokeTest.php`, contra la BD local): super entra a todos los índices; "Cliente Verficado" entra a `/prestamos` y recibe 403 en `/banks`; endpoint JSON OK. **42/42 tests en verde.**
- [ ] `authorize()` real en los FormRequests (hoy `true`; la ruta ya está protegida por `permission:`). Endurecer en Fase 9.
- [ ] Wayfinder: generar acciones tipadas para el front (se hace al portar cada pantalla, Fase 6).

### Fase 5 — Shell de frontend · 2–4 días — **✅ COMPLETADA**

- [x] `vite.config.ts` ya trae Vite + `@vitejs/plugin-vue` + Wayfinder + Tailwind 4 (starter kit). Añadido `public/build/**` a `lint.ignorePatterns`.
- [x] `resources/js/app.ts`: `setup()` propio con `createApp` + `createPinia` + `ElementPlus` (locale `es`) + iconos de element-plus + directiva `v-can`. CSS de element-plus (claro + dark-vars) importado.
- [x] **Sidebar dinámico**: `components/NavMenu.vue` consume `page.props.menu` (árbol servido por `App\Support\Menu`, ya filtrado por `can()` en el backend). `App\Support\Menu` ahora emite `url` (resuelta desde el nombre de ruta) en vez del nombre. `AppSidebar` usa `NavMenu` (se quitan los items hardcodeados y `NavFooter`).
- [x] Helper **`resources/js/lib/can.ts`** (`can`, `canAny`, `canAll`, `hasRole`, `isSuperAdmin`) + directiva **`v-can`** (elimina el elemento sin permiso). Tipos: `Auth` con `roles`/`permissions`, `MenuNode`, `sharedPageProps.menu`.
- [x] **Verificado en navegador**: login como `enrony` → dashboard + sidebar con el menú completo (Registros/Procesos/Mantenimiento) filtrado por permisos; `element-plus` y Pinia cargan sin errores. Las rutas de módulo llegan al controlador y fallan solo al resolver el componente Vue (Fase 6).
- [ ] Pendiente menor (Fase 9): `app.js` pesa ~1,2 MB (element-plus completo) → auto-import por componente.

### Fase 6 — Migración de pantallas por módulo · 2–4 semanas _(el grueso)_

**Andamiaje establecido con Préstamos** (reutilizable en todas las pantallas): `lib/http.ts` (axios + XSRF), `lib/format.ts`, patrón store Pinia (`stores/prestamos.ts`) + `el-table` con paginación/orden server-side + `el-pagination` + barra de filtros + `flash.toast` (backend en `HandleInertiaRequests`, front en `lib/flashToast.ts` sobre `router.on('success')`).

Orden por criticidad de negocio:

1. [ ] **Préstamos** — 🟡 **listado + asistente de alta hechos**
    - **Listado** (`pages/Prestamos.vue` + `components/prestamos/{PrestamosTable,PrestamosFilters}.vue` + `stores/prestamos.ts`): tabla con datos reales, filtros (clientes remoto, rangos de fecha, estado), orden y paginación contra `POST /prestamos/records`; `el-tag` de estado desde `p_estatus.type_tag`. **Verificado en navegador.**
    - **Asistente de alta** (`components/prestamos/{PrestamoFormModal,CuotasGrid}.vue` + `lib/prestamoSchedule.ts`): `el-dialog` de 2 pasos (cliente → parámetros). `lib/prestamoSchedule.ts` = port fiel de `generaListPays`/`calculateLastDate`/`applySurchage` de moment+lodash a dayjs (rondeo por tramos, cuota final ajustada, recargo por mora). Store: `fetchTables`/`seleccionarCliente`/`generar`/`aplicarRecargo`/`submitPrestamo`. **Verificado en navegador** (modal → cliente + ficha → paso 2 → país → Generar produce calendario) y **backend probado**: `PUT /prestamos` crea `Prestamos` + N `prestamos_dias` con `grupos_trabajos_user_id` resuelto.
    - [ ] **Fase 7**: tests unitarios de `lib/prestamoSchedule.ts` (correctitud numérica vs. el sistema legado).
    - [x] **Fila expandible** (`el-table type="expand"`): `PrestamoLegend.vue` (contadores Cuotas/Pagadas/Pendientes/Demoradas) + `CuotasReadonlyGrid.vue` (grilla del calendario con color por estado: pagada verde, demorada roja, pendiente índigo, hoy azul). Datos ya vienen en `Prestamos::lista` (`prestamos_dias`). **Verificado en navegador.**
    - [x] **Cambio de fecha de una cuota** (en el asistente de alta, como el legado): al generar el calendario, clic en una cuota abre un modal para moverla. `store.cambiarFechaCuota()` port de `changeDate.vue` — valida festivo/domingo según la config del préstamo y que la fecha quede en `[first_pay, last_pay]`; marca `date_change`/`date_before`, recalcula `sigla`/`festivo`/`dom`/`diff`. **6 tests unitarios** (`stores/__tests__/prestamos.cambiarFecha.test.ts`).
    - [x] **Alta rápida de cliente** (`components/prestamos/NuevoClienteModal.vue`): botón "+ Nuevo" junto al select de cliente en el paso 1. Formulario con cascada país → (tipos de documento + departamento) → ciudad (`GET /clientes/tables` + `GET /api/listaTipoDocu`), campos obligatorios validados. `PUT /user/actualizaCliente` con `otherForm: true` → `{ success, id }` → recarga `clientesAll` y **preselecciona el cliente nuevo** en el asistente. Feature test del alta.
    - [x] **Pausar / reanudar recargo por mora**: nuevo endpoint `PUT /prestamos/{id}/pausar-recargo` (`permission:prestamos.editar`) que alterna `prestamos.pause_surcharge`. En `PrestamosTable` la acción del dropdown cambia entre "Pausar recargo" / "Reanudar recargo" y muestra ⏸ junto al id. **De paso se arregló el comando `AplicarRecargosPrestamos`** (usaba la columna inexistente `estado` en vez de `estatus`, faltaba `use CountryHoliday`) y ahora **respeta `pause_surcharge`**. Feature tests del toggle y de que el comando corre.
    - [ ] **"Novedades"** _(pendiente, hacer luego)_: en el legado era un `<label>` muerto en el dropdown de acciones del préstamo, sin `store`, sin ruta, sin tabla — nunca se implementó ni se especificó. **Anotado para retomar**: falta que el negocio defina qué es una "novedad" (¿nota/incidencia por préstamo? ¿bitácora con estado?), su modelo de datos y sus permisos. No bloquea la migración.
    - [x] El módulo de **Clientes** como pantalla completa está hecho — ver punto 3.
    - [ ] `TipoPrestamo` (maestro de préstamos). `Frecuencias` ya está hecho en §4 Maestros.
2. 🟡 **Informes de pago** — **listado hecho** (`pages/PaymentReport.vue` + `components/paymentReport/{PaymentReportTable,PaymentReportFilters}.vue` + `stores/paymentReport.ts`): `el-table` con datos reales (`GET /payment_report/records`), columnas id/registrado/cliente/destino/monto (`value_amount`)/nº cuotas/estado (`el-tag` desde `status_description.color`); filtros clientes/fecha/estado (`GET /PaymentReportsMovementsEstatu/tables`)/destino del pago; paginación. **Verificado en navegador** (16 informes) + smoke test.
    - [x] **Cambio de estado** (`components/paymentReport/ChangeStateModal.vue` + `store.changeState`): desde la fila, modal con el estado actual + `el-select` de acciones ofrecibles (`estados` menos el actual, deshabilitado si `finish_estatus`), textarea de **motivo** cuando el estado destino lo requiere (Rechazado/En revisión/Remitido). `POST /payment_report/change_estatus_report` (multipart, `data` JSON). Backend: `PaymentReportService` — arreglado el bug `update(['error_process', true])` → `['error_process' => true]`. Al aprobar (estado 2) marca cuotas `pagado` y registra saldo a favor. **Probado backend + feature test** (Pendiente → En revisión crea movimiento y actualiza el estatus); modal verificado en navegador. Subida de soporte diferida (ningún estado actual la exige).
    - [x] **"Informar un pago"** (`components/paymentReport/InformarPagoModal.vue` + store): modal con cliente + destino (cuotas / saldo a favor); si es cuotas, `el-collapse` de préstamos activos (`GET /prestamos/obtenerPrestamosActivos/{id}`) con checkboxes de cuotas pendientes (`apply && !pagado`); filas repetibles de métodos de pago (`GET /payment_methods/tables`) con banco/franquicia/referencia condicionales según los flags del método; totales (cuotas / pagos / saldo a favor). `POST /payment_report/` → `PaymentReportService` (createPaymentReport + registerPaymentMehods + registerPaymentMovement + registerPaymentCuotas + registerPositiveBalance). **Probado backend + feature test** (crea informe con 2 cuotas + método, `importe` = saldo a favor bien calculado) + modal verificado en navegador. Subida de soporte por método diferida.
    - [ ] Pendiente menor: preview/gestión de soportes, `showCuotasSelected` (detalle de un informe existente), `Management.vue` (multi-informe).
    - ⚠️ Fase 9: los endpoints auxiliares (`/payment_methods/tables`, `/banks/tables`, `/prestamos/obtenerPrestamosActivos`) están bajo `permission:<otro-módulo>.listar`; un usuario con solo `payment_report.*` recibiría 403. Reubicar o relajar.
3. [x] **Clientes** — pantalla completa (`pages/Clientes.vue` + `components/clientes/{ClientesTable,ClienteFormModal}.vue` + `stores/clientes.ts`):
    - **Listado**: `GET /clientes` (Inertia) con paginador server-side (15/pág), filtro de texto (nombre/apellido/documento) con debounce vía `router.get(..., { only: ['lista'] })`. Columnas: documento (`sigla-documento`), nombre completo, teléfono, correo, registrado. Scope por grupo de trabajo salvo super. El `index` usa **query builder** (no el modelo) para evitar los `$appends` N+1/frágiles (`prestamos_activos`, `country_id` vía `city`). **Verificado en navegador** (5 clientes).
    - **Alta/edición** (`ClienteFormModal`): cascada país → departamento → ciudad + tipo de documento filtrado por país (reusa la lógica ya probada del alta rápida del asistente de préstamos). `PUT /clientes` → `ClientesController@actualizaCliente` (rama redirect, valida y crea/actualiza). Errores de Inertia por campo. **Feature test** `test_clientes_crud` (alta + edición + baja lógica).
    - **Baja lógica**: `DELETE /clientes/{id}/{page}` → `estatus = 0` (el listado filtra `estatus = 1`). Con confirmación.
    - **Fase 9 (resuelto de paso)**: `GET /clientes` estaba en `routes/prestamos.php` bajo `permission:prestamos.listar`; se movió a `routes/clientes.php` (gate `clientes.listar`).
    - [ ] Pendiente menor: el modal "Consulta y registro de préstamos" desde la fila del cliente (en el legado era un mock con datos falsos).
4. 🟡 **Maestros** — **CRUD genérico** `components/maestros/{MaestroCrud,MaestroFormModal}.vue` + `types.ts`: config declarativa (columnas + campos text/number/switch/select), tabla + `el-pagination` + modal de alta/edición (`PUT /<recurso>`) + borrado con confirmación (`DELETE /<recurso>/{id}/{page}`), todo gateado por `can('<recurso>.registrar|editar|eliminar')`. Errores de validación de Inertia mostrados en el form.
    - [x] `PaymentForm`, `PaymentMethod` (con columnas/switches booleanos), `Franquicias`, `BankAccountType`, `typePaymentRecord` — páginas de ~25 líneas cada una. **Verificado en navegador** (Franquicias, Métodos de pago) + **CRUD probado** (crear/editar/borrar Franquicia) + smoke tests.
    - [x] **Selects/lookups dinámicos**: `MaestroField` acepta `optionsKey`/`optionLabel`/`optionValue` (opciones desde el endpoint `tablesUrl`) y `MaestroColumn` acepta `lookupKey`/`lookupLabel` (resuelve un id contra `tables[...]` para mostrarlo). `MaestroCrud` hace `onMounted` fetch de `config.tablesUrl` y lo pasa al form; el `el-select` es `filterable`.
    - [x] `Bank` (+ select de país vía `/banks/tables` → `CountryAll`), `TipoDocumentos` (+ país), `Frecuencias` (+ tipo de frecuencia vía `TipoFrecuenciaPrestamoAll`). **Verificado en navegador** (`/banks`: 146 filas, columna País resuelta por lookup) + smoke test (8 páginas de maestros + los dos endpoints de datos). De paso se arregló `BankController@index` (el `selectRaw` omitía `code` → columna "Código" vacía).
    - [x] **Campo `date`** en el CRUD genérico (`el-date-picker` con `value-format="YYYY-MM-DD"`) + `config.labelProp` para el texto del diálogo de confirmación de borrado.
    - [x] `Festivos` (nombre + fecha + año + país; `year` lo calcula el backend desde `date`), `Departments` (nombre + país), `Cities` (nombre + país + departamento, doble lookup contra `/cities/tables` → `CountryAll`+`DepartmentAll`), `gruposTrabajo` (nombre + código + ciudad + referencia, lookup `CitiesAll`). **Verificado en navegador** (`/cities`: departamento y país resueltos por lookup; `/festivos`: 132 filas con fecha/año) + smoke test amplía a 12 páginas de maestros + `/departamentos/tables`, `/cities/tables`, `/grupos_trabajo/tables`.
    - **Maestros: 12/13 hechos.** Falta `TipoPrestamo` (maestro de préstamos, en §1).
5. 🟡 **Admin / RBAC** — spatie, reemplaza las pantallas legadas `Profiles`/`Action`/`Modules` (ver §11):
    - [x] **Roles y permisos** (`pages/admin/Roles.vue` + `components/admin/RoleFormModal.vue` + `stores/roles.ts`): `GET /profile` lista los roles spatie (nº de permisos, nº de usuarios, marca "protegido" para `super-admin`). Modal de alta/edición con el **catálogo de permisos agrupado por módulo** (checkbox por módulo con estado indeterminado + `el-checkbox-group` por habilidad). `PUT /profile` → `RolesController@store` (`syncPermissions`), `DELETE /profile/roles/{role}` (bloquea `super-admin`). Gates `profile.editar` / `profile.eliminar`. **Verificado en navegador** + feature test `test_admin_roles`.
    - [x] **Usuarios y roles** (`pages/admin/Usuarios.vue`): `GET /profile/usuarios` (paginado + búsqueda), `el-select` múltiple de roles por fila que guarda al vuelo con `PUT /profile/usuarios/{user}` → `syncRoles`. **Verificado en navegador** + feature test `test_admin_usuarios`.
    - [x] **Fase 9 (de paso)**: `routes/Profile.php` apuntaba a la pantalla legada (`App\Http\Controllers\ProfileController` + `Profiles.vue`); ahora apunta a `App\Http\Controllers\Admin\{Roles,Users}Controller`. Los controladores/páginas/modelos legados (`ProfileController`, `ActionController`, `ModulesController`, `Modules`, `Profiles`, …) quedan como **código muerto a borrar** junto con las tablas de §11.
    - [ ] **Editor de menú (`menu_items`)** _(pendiente)_: el árbol de navegación se siembra de `rbac:sync-from-legacy` y cambia poco. Un editor de árbol (reordenar, anidar, icono, permiso por ítem) es fiddly y de bajo valor ahora — se hará si el negocio necesita tocar el menú sin deploy. `routes/modules.php` sigue apuntando al `ModulesController` legado (sin UI nueva).
6. 🟡 **Perfil de usuario + 2FA** — el starter kit ya trae el flujo completo (perfil, contraseña, **2FA TOTP con confirmación**, **passkeys**, apariencia, borrar cuenta). El trabajo de la migración fue **traducir todo a español** y verificar:
    - [x] `layouts/settings/Layout.vue`, `pages/settings/{Profile,Security,Appearance}.vue` + `components/{ManageTwoFactor,ManagePasskeys,PasskeyItem,PasskeyRegister,TwoFactorRecoveryCodes,TwoFactorSetupModal,DeleteUser,AppearanceTabs,UserMenuContent}.vue` traducidos.
    - [x] Lado de login del 2FA: `pages/auth/{TwoFactorChallenge,ConfirmPassword}.vue` traducidos (el resto de páginas de auth —Login/Register/Forgot/Reset/VerifyEmail— quedan para un pase de i18n de auth, Fase 9).
    - [x] **Verificado en navegador** (`/settings/profile` en español; nav Perfil/Seguridad/Apariencia) + feature test `test_settings_perfil` (índice resuelve + `PATCH` de nombre).
    - ⚠️ **A decidir con el negocio**: `DeleteUser` (auto-baja de cuenta con borrado en cascada) sigue visible en el perfil — para un back-office quizá convenga quitarlo o gatearlo por permiso.
    - Sin Teams (el starter kit Vue no los trae).

Por cada SFC: migrar a Vue 3 (filtros fuera, `.sync` → `v-model:arg`, bus de eventos, `v-model`), ajustar
`el-*` a `element-plus` (props/slots/nombres), revisar store Pinia, probar contra datos reales importados.

### Fase 7 — Cálculos financieros + tests · 3–5 días _(en paralelo a Fase 6)_

- [x] **Runner de tests JS**: `vp test` (vitest 4), script `npm test`. `resources/js/lib/__tests__/`.
- [x] **`lib/prestamoSchedule.test.ts`** (9 casos): `calcLastDate` (incl. recorte de mes corto); reparto exacto (10 cuotas iguales); redondeo por tramos + ajuste de la última cuota (7 cuotas → 6×42800 + 43200 = 300000); exclusión de domingos; exclusión de festivos; cuota sugerida (desmarca las que exceden el total); `applySurcharge` (N días hábiles saltando domingos) + caso desactivado. Valores esperados trazados a mano desde `generaListPays` del legado.
- [ ] Tests (Pest): `PaymentReportService`, precisión `DECIMAL`, flujo de informe de pago.
- [ ] Feature tests: crear préstamo (backend), registrar pago, informe de pago con soporte, cambio de estado.
- [ ] Comparación de salidas nuevo vs sistema viejo con un set de casos reales documentado.

### Fase 8 — Integraciones · 2–3 días

- [ ] Storage S3; revisar si el parche histórico de `FilesystemAdapter::putFileAs` sigue haciendo falta en Flysystem 3.
- [ ] Doble mailer (`MAIL_` / `MAIL_2`) → dos mailers nombrados en `config/mail.php`.
- [ ] Broadcasting: evaluar si Pusher sigue en uso; si sí, `laravel-echo` vía Vite; si no, quitar.
- [ ] Cache/colas: **`database` o `redis`** según lo que ofrezca la cuenta Ferozo (el server tiene `redis` en `lsphp83`). Colas: `queue:work` como proceso o cron.
- [ ] Tareas programadas (`ScheduledTaskLog`) + entrada de cron con ruta `lsphp83` explícita.

### Fase 9 — QA y hardening · 3–5 días

- [ ] Regresión completa por checklist de módulos.
- [ ] Seguridad: subida de archivos por magic bytes, CSRF, **autorización por permiso en cada ruta**, aislamiento por país/franquicia/grupo de trabajo (query scoping), hashing de contraseñas.
- [ ] `pint` + `larastan` en verde. `.env.production.example` sin secretos.

### Fase 10 — Despliegue y cutover en Ferozo · 2–3 días

- [ ] Preparación única en el server (§12): clone, `composer83 install --no-dev --no-scripts`, `package:discover`, `.env`, `key:generate`, `migrate --force`, mover a `public_html/<app>_app`, `.htaccess` de protección, `storage:link`, `config/route/view:cache`, document root, cron.
- [ ] Import de datos de producción (dump fresco de la BD viva).
- [ ] Smoke test: login, préstamos, informe de pago, permisos. `config('app.debug') === false`. `.env` → 403.
- [ ] Cambio de document root / DNS al nuevo. **Rollback** = repuntar al viejo (BD intacta).
- [ ] Archivar repo `sisconpre`.

---

## 7. Mapa de equivalencias de paquetes

| Actual                                          | Nuevo                                                    |
| ----------------------------------------------- | -------------------------------------------------------- |
| `laravel/framework ^8`                          | `^13.17`                                                 |
| `laravel/jetstream ^1.3`                        | **eliminado** → `laravel/fortify` + starter kit Vue      |
| `laravel/sanctum ^2.6`                          | `^4` (solo si hay API)                                   |
| `inertiajs/inertia-laravel ^0.2`                | `^3`                                                     |
| `tightenco/ziggy`                               | **eliminado** → `laravel/wayfinder`                      |
| `spatie/laravel-permission ^5.11`               | `^6`                                                     |
| `fideloper/proxy`                               | eliminado (`TrustProxies` en el core)                    |
| `fruitcake/laravel-cors`                        | eliminado (CORS nativo)                                  |
| `facade/ignition`                               | `spatie/laravel-ignition`                                |
| `doctrine/dbal`                                 | innecesario (cambio de columnas nativo)                  |
| `nunomaduro/collision ^5`                       | `^8`                                                     |
| `phpunit/phpunit ^9`                            | **Pest** (`phpunit ^12`)                                 |
| —                                               | `laravel/pint`, `larastan/larastan` _(nuevos, calidad)_  |
| `laravel-mix ^5` + webpack                      | `vite` + `laravel-vite-plugin`                           |
| `vue ^2.5`                                      | `vue ^3.5`                                               |
| `@inertiajs/inertia` + `@inertiajs/inertia-vue` | `@inertiajs/vue3 ^3`                                     |
| `element-ui`                                    | `element-plus` _(se mantiene como librería de negocio)_  |
| `@vue/composition-api`                          | nativo                                                   |
| `portal-vue`                                    | `<Teleport>` nativo                                      |
| `vue-js-modal`                                  | `el-dialog`                                              |
| `toastr` + `vue-toastr-2`                       | `element-plus` (`ElNotification`) o `vue-toastification` |
| `moment`                                        | `dayjs`                                                  |
| `alpinejs ^2`                                   | `^3` (o eliminar si Inertia cubre el caso)               |
| `tailwindcss ^1` + `@tailwindcss/ui`            | `^4` (del starter kit)                                   |

---

## 8. Inventario de dominio a portar

**Controladores:** ~40 · **Modelos:** ~46 · **Archivos de rutas:** ~24 · **Migraciones:** ~73 · **Carpetas de pantallas Vue:** ~25 · **Servicios:** `PaymentReportService` · **Tablas en BD:** 57

Agrupación funcional:

- **Préstamos:** `Prestamos`, `PrestamosDias`, `PrestamosEstatu`, `PrestamosTarifa`, `TipoPrestamo`, `TipoFrecuenciaPrestamo`, `Frecuencias`
- **Informes de pago:** `PaymentReport`, `PaymentReportsMovement`, `PaymentReportsMovementsEstatu`, `PaymentReportsSupportMovement`, `SelectedPaymentReport`, `SupportPaymentReport`, `paymentReportsMethod`, `supportPaymentReportsMethod`, `PaymentForm`, `PaymentMethod`, `TypePaymentRecord`, `TypesMovement`, `SummaryCustomerMovement`, `CustomerMovementHistory`
- **Clientes:** `Clientes`, `ClientesGruposTrabajosUsers`, `tiposDocumentos`
- **Organización / geografía:** `Franquicia`, `GruposTrabajo`, `GruposTrabajoUser`, `UserCountry`, `Country`, `CountryHoliday`, `Department`, `City`, `State`, `Moneda`
- **Bancos:** `Bank`, `BankAccountType`
- **RBAC / Admin:** `Action`, `Modules`, `ModulesActions`, `ModulesRelation`, `ModulesActionsProfiles`, `Profiles`, `ProfilesUsers` → **consolidar en spatie + `menu_items`** (§11)
- **Config:** `ConfigurationItem`
- **Usuario:** `User` (perfil + 2FA vía Fortify). **Descartar:** `Team`, `Membership`, `team_user`

---

## 9. Riesgos y mitigaciones

| Riesgo                                                                             | Mitigación                                                                                                                          |
| ---------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| `element-ui` → `element-plus` no es 1:1 (riesgo de cronograma #1)                  | Portar por módulo con prueba visual contra datos reales; holgura en Fase 6                                                          |
| **No hay autorización real en el servidor hoy** (solo menú dinámico)               | Consolidar en spatie (§11) + `permission:` en cada ruta + `authorize()` real + Policies (Fase 3/4)                                  |
| Dos sistemas de UI en una misma app (reka-ui en el shell, element-plus en negocio) | Aceptado. Aislar el shell del área de negocio; convergencia futura módulo a módulo                                                  |
| Cálculos financieros sin tests hoy                                                 | Fijar con tests (Fase 7) **antes** de refactorizar; comparación nuevo vs viejo                                                      |
| MySQL 5.7 en el server (por debajo del mínimo documentado de L13)                  | `crm_gilen` ya corre L13 sobre 5.7. Evitar CTE / window functions / functional indexes en SQL crudo; revisar `PaymentReportService` |
| `proc_open` deshabilitado en el server                                             | `composer install --no-scripts` + `php83 artisan package:discover` manual (ya documentado en el runbook del CRM)                    |
| Dump es de ~mayo 2025, no del estado actual                                        | Portar las migraciones del repo hasta la última; en el cutover, dump fresco de la BD viva                                           |
| `public/build/` versionado se desincroniza del código                              | Disciplina de release: compilar en local y commitear `public/build/` en el **mismo** commit que el cambio de front                  |
| Deriva entre código viejo y nuevo durante la migración                             | Congelar features en `sisconpre`; solo fixes críticos, replicados a mano                                                            |

---

## 10. Prerrequisitos / pendientes tuyos

1. ~~Repo nuevo~~ → **✅ `sisconpre-v2` creado y validado.**
2. ~~Dump de BD~~ → **✅ recibido** (`...\Downloads\respaldo_db.sql\prestamos_db.sql`).
3. ~~Versión de CentOS / Docker o bare-metal~~ → **✅ VPS Ferozo, CentOS 7 + LiteSpeed, sin Docker, `lsphp83` (PHP 8.3), MySQL 5.7.** Despliegue nativo (§12).
4. ~~RBAC~~ → **✅ consolidar en spatie** (§11).
5. ~~Auth scaffold~~ → **✅ Fortify + starter kit Vue, sin Jetstream.**
6. ~~UI frontend~~ → **✅ portar a element-plus.**
7. ~~Nombre del subdominio~~ → **✅ `prestamos.gilensoft.com`**, document root **✅ `prestamos_app/public`**.
8. ~~BD MySQL~~ → **✅ `gilen_prestamos`** (usuario `gilen_prestamos`). **Pendiente:** anotar la clave para el `.env`.
9. **Pendiente:** SSL **Let's Encrypt + "Redirección https"** para el subdominio (aún sin certificado).
10. **Pendiente:** PAT de GitHub (classic, scopes `repo` + `workflow`) para el clone en el servidor.
11. **Pendiente:** carpeta `~/public_html/prestamos_app` creada (vacía) — se llena al clonar en la Fase 1.
12. **A confirmar durante la Fase 8:** ¿el módulo de broadcasting (Pusher) y el de API tokens siguen en uso, o se retiran?

---

## 11. Consolidación de RBAC en `spatie/laravel-permission`

### Qué hay hoy

7 tablas propias: `modules`, `modules_relations`, `actions`, `modules_actions`, `profiles`,
`modules_actions_profiles`, `users_profiles`.

```
User ──< users_profiles >── Profile ──< modules_actions_profiles >── (Module × Action)
Module ──< modules_relations >── Module        (árbol del menú lateral)
```

**Hallazgo de la auditoría:** _no hay control de acceso real en el servidor._ Los `FormRequest::authorize()`
devuelven `true`, no hay middleware `can:`/`permission:`, no hay `Gate`, y no hay `HandleInertiaRequests`
compartiendo permisos. Las tablas `modules`/`profiles` hoy **solo pintan el menú dinámico** y ocultan botones
en Vue. `isSuperUsuario()` solo amplía consultas (ver todos los grupos), no bloquea. La migración es también
la oportunidad de **añadir autorización de verdad por primera vez**.

### Separar dos cosas que el sistema propio mezcla

| Concern                                                    | Hoy                                                        | En el proyecto nuevo                                                                                                                        |
| ---------------------------------------------------------- | ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| **Autorización**                                           | `profiles` + `modules_actions_profiles` + `users_profiles` | **spatie**: `roles` + `permissions` + `role_has_permissions` + `model_has_roles`                                                            |
| **Navegación** (árbol, icono, orden, ruta, `visible_menu`) | `modules` + `modules_relations`                            | tabla `menu_items` (`parent_id`, `label`, `icon`, `route`, `order`, `permission`) — sin semántica de permiso, solo `permission` como filtro |

### `actions` reales (del dump) → habilidades

`01 Acceso Total`, `02 Registrar`, `03 Editar`, `04 Duplicar`, `05 Eliminar`, `06 Imprimir`, `07 Exportar`,
`08 Importar`, `09 Anular`, `10 Eliminar masivo`, `11 Listar`, `12 Gestionar informe de pago`.
**Excluir** `13 Prueba` y `14 Pruba 2`.

### Convención de nombres de permiso

`"<recurso>.<habilidad>"` en kebab-case: `<recurso>` desde `modules.clave`; `<habilidad>` desde el slug del
nombre de la acción → `prestamos.registrar`, `prestamos.editar`, `prestamos.eliminar`, `prestamos.listar`,
`informes-pago.gestionar`, `informes-pago.anular`, `clientes.registrar`, … `01 Acceso Total` sobre un módulo
=> conceder todas las habilidades de ese módulo. Generar un **enum `App\Auth\Permission`** con el catálogo.

### Mapa de migración de datos (script único, Fase 3)

| Origen                                   | Destino spatie                                                                                   |
| ---------------------------------------- | ------------------------------------------------------------------------------------------------ |
| cada `profiles` (sin las de prueba)      | `roles` (guard `web`), `name` = nombre del perfil                                                |
| cada `users_profiles`                    | `model_has_roles` (`assignRole`)                                                                 |
| cada `modules_actions` (Module × Action) | `permissions` con nombre `"<recurso>.<habilidad>"`                                               |
| cada `modules_actions_profiles`          | `role_has_permissions` (`$role->givePermissionTo(...)`)                                          |
| `profiles.su = true`                     | `assignRole('super-admin')` + `Gate::before(fn($u) => $u->hasRole('super-admin') ? true : null)` |
| `profiles.admin = true`                  | rol `admin` con su set de permisos                                                               |
| `modules` + `modules_relations`          | filas en `menu_items`                                                                            |

### Enforcement (usando lo que trae Laravel)

- **Rutas:** `->middleware('permission:prestamos.registrar')`.
- **Controladores / FormRequests:** `authorize()` real (`$this->user()->can('prestamos.editar')`) o **Policies** por modelo respaldadas por permisos.
- **Frontend:** `HandleInertiaRequests` comparte `auth.user.roles` y `auth.user.permissions`; helper `can()` / directiva `v-can`. El botón se oculta **y** la ruta bloquea.
- **Menú:** se arma en el servidor desde `menu_items` filtrando por `can(item.permission)`.

### Scoping de datos (no es RBAC)

El aislamiento por **grupo de trabajo / franquicia / país** (`grupos_trabajos_user_id`, 50 usos en
controladores; `obtenerGrupoTrabajo()`) se mantiene como **capa de filtrado de consultas** (global scope /
`when()` + un scope reutilizable), **no** como permisos spatie. Roles y permisos son **globales**.

### Tablas a eliminar tras la migración

`modules_actions_profiles`, `users_profiles`, `profiles`, `modules_actions`, `actions`, `modules`, `modules_relations`
(estas dos últimas reconvertidas a `menu_items`).

---

## 12. Despliegue nativo en el VPS Ferozo

**Base:** replicar el runbook real de `crm_gilen/docs/despliegue.md` (deploy hecho el 28-ago-2026). **Sin Docker,
sin Node en el server.**

### Hechos del servidor

| Cosa         | Detalle                                                                                                                                                |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| SO / web     | CentOS 7 + **LiteSpeed**, panel propio Ferozo (no cPanel), cuenta `gilen` (`nologin`; entrar con `su -s /bin/bash - gilen`)                            |
| Ubicación    | `/home/gilen/public_html/<app>_app/`, document root del subdominio → `<app>_app/public`                                                                |
| PHP          | `php` = 5.6 (no usable). Usar **`/usr/local/lsws/lsphp83/bin/php`** (PHP 8.3, todas las extensiones + redis/imagick/gd/memcached/gmp)                  |
| Composer     | `composer83`; **`proc_open` deshabilitado** → `composer install --no-dev --optimize-autoloader --no-scripts` + `php83 artisan package:discover` a mano |
| MySQL        | 5.7.44, BD + usuario por cuenta desde el panel                                                                                                         |
| Node         | v16, inservible → **`public/build/` se commitea** desde local                                                                                          |
| Git          | 1.8.3.1, solo HTTPS; repo privado → PAT classic (`repo`+`workflow`) + `credential.helper store`                                                        |
| open_basedir | sin symlinks fuera de `public_html`; la raíz del proyecto se protege con `.htaccess` (`RewriteRule ^ - [F,L]`)                                         |

### Alias de sesión (`gilen`)

```bash
alias php83='/usr/local/lsws/lsphp83/bin/php'
alias composer83='/usr/local/lsws/lsphp83/bin/php /usr/local/bin/composer83'
```

### Preparación única

1. **Panel Ferozo:** subdominio con **PHP 8.3 FPM**, BD MySQL + usuario, **SSL Let's Encrypt** + "Redirección https".
2. **Clone y montaje:**
    ```bash
    git clone https://<user>:<token>@github.com/enrony/sisconpre-v2.git
    cd sisconpre-v2
    git remote set-url origin https://github.com/enrony/sisconpre-v2.git
    git config credential.helper store
    composer83 install --no-dev --optimize-autoloader --no-scripts
    php83 artisan package:discover
    cp .env.production.example .env       # editar DB_*, APP_URL, MAIL_*, S3, etc.
    php83 artisan key:generate
    php83 artisan migrate --force
    php83 artisan db:seed --force          # solo maestros + usuario admin
    ```
3. **Mover a `public_html` y proteger la raíz:**
    ```bash
    mv ~/sisconpre-v2 ~/public_html/prestamos_app
    cd ~/public_html/prestamos_app
    printf 'RewriteEngine On\nRewriteRule ^ - [F,L]\n' > .htaccess
    rm -f public/storage
    mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} storage/logs
    chmod -R ug+rwx storage bootstrap/cache
    php83 artisan storage:link
    php83 artisan config:cache && php83 artisan route:cache && php83 artisan view:cache
    ```
4. **Panel Ferozo:** document root del subdominio → `prestamos_app/public`. Reiniciar LiteSpeed.
5. **Cron (panel Ferozo):** los 5 campos en `*`, comando:
    ```
    /usr/local/lsws/lsphp83/bin/php /home/gilen/public_html/prestamos_app/artisan schedule:run
    ```
6. **Verificar:** subdominio → `/login`; `https://gilensoft.com/prestamos_app/.env` → **403**; `config('app.debug')` → `false`.

### Release (cada actualización)

1. **En local:** `npm run build` → commit de código **+ `public/build/`** juntos → push.
2. **En el server** (como `gilen`):
    ```bash
    cd ~/public_html/prestamos_app
    php83 artisan down
    git pull
    composer83 install --no-dev --optimize-autoloader --no-scripts
    php83 artisan package:discover
    php83 artisan migrate --force
    php83 artisan config:cache && php83 artisan route:cache && php83 artisan view:cache
    php83 artisan up
    ```

### Colas / broadcasting

- Colas: si se usan, `QUEUE_CONNECTION=database` + una entrada de cron que lance `queue:work --stop-when-empty` cada minuto (LiteSpeed no gestiona workers persistentes cómodamente); o `redis` si la cuenta lo permite.
- Broadcasting: confirmar en Fase 8 si Pusher sigue vivo; si no, retirar.
