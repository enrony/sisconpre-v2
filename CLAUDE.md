# CLAUDE.md — sisconpre-v2 (`prestamos_gilen`)

Sistema de gestión de préstamos. **Migración** del legado Laravel 8 `prestamos_16`
(`C:\Docker\prestamos_16`) a Laravel 13. Repo: `github.com/enrony/sisconpre-v2`.
El detalle completo del plan y el estado por fase está en **`PLAN_MIGRACION.md`** — leerlo
para cualquier trabajo de migración.

## Stack

- **Laravel 13** + PHP 8.3 · Fortify + `laravel/vue-starter-kit` (Inertia 3, `@inertiajs/vue3` ^3).
- **Vue 3.5** + Vite (`vite-plus` / CLI `vp`) + Pinia. Rutas tipadas: **Wayfinder** (no Ziggy).
- **element-plus** para las pantallas de negocio (on-demand vía `unplugin-*`; locale `es` por `ElConfigProvider`). reka-ui/Tailwind 4 en el shell.
- RBAC: **`spatie/laravel-permission` v8**. Aliases `role`/`permission`/`role_or_permission` en `bootstrap/app.php`. `permission:a|b` = ANY.
- Tests: **Pest/PHPUnit 12**, Pint, **Larastan level 7** (con `phpstan-baseline.neon` para deuda legada).

## Comandos (local)

```bash
php artisan test              # suite PHP (corre contra MySQL: ver phpunit.xml)
./vendor/bin/pint             # formato PHP
./vendor/bin/phpstan          # análisis estático (nivel 7 + baseline)
npm run check                 # fmt + lint front
npm run types:check           # vue-tsc --noEmit
npm run build                 # regenera public/build/ (OBLIGATORIO antes de commitear cambios de front)
npm run test                  # vitest
```

- `php artisan test` usa la BD MySQL `prestamos_gilen_test` (stock, `RefreshDatabase`); las suites propias
  (`PanelSmokeTest`, `PaymentReportServiceTest`, `FormRequestAuthorizationTest`, `MailIntegrationTest`)
  apuntan a `prestamos_gilen` (con datos importados) desde su `setUp()` y se auto-limpian.

## Convenciones

- **`public/build/` va commiteado** (el server no tiene Node). Correr `npm run build` antes de cada commit que toque el front.
- Rutas por módulo en `routes/<modulo>.php`, cada archivo envuelto en `Route::middleware([...,'permission:<clave>.listar'])` vía el `$panel` map de `bootstrap/app.php` (`withRouting(then:)`). Endpoints transversales sin gate de módulo → `routes/shared.php`.
- CRUD genérico de maestros: `components/maestros/{MaestroCrud,MaestroFormModal}.vue` + `types.ts` config-driven.
- Un store Pinia por módulo en `resources/js/stores/`.
- **Git**: Claude hace `add` + `commit` + `push origin main` directamente (directo a `main`, sin ramas). Mensajes en español + trailer `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`.

## Producción — VPS Ferozo (`prestamos.gilensoft.com`)

- **Todos los comandos en el server se corren como el usuario `gilen`**, nunca root (`su - gilen -c '...'`).
- Proyecto: `/home/gilen/public_html/prestamos_app` · docroot `.../public`.
- PHP: `/usr/local/lsws/lsphp83/bin/php` · Composer: `composer83` · `proc_open` deshabilitado (`--no-scripts` + `package:discover` manual).
- MySQL 5.7: no se puede `CREATE DATABASE` por consola (solo panel, nombre máx 16 chars). BD `gilen_prestamos`.
- Git 1.8.3.1, solo HTTPS + PAT (embebido en la URL del remoto).
- Release: `su - gilen -c 'cd /home/gilen/public_html/prestamos_app && git pull && /usr/local/lsws/lsphp83/bin/php artisan route:cache'`.
- Cron del proyecto: `schedule:run` en `crontab -u gilen`. El cron de otro proyecto (betplaye) vive en `/etc/crontab` — no tocar.
