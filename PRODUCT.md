# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Personal interno de una financiera/prestamista (no clientes finales): agentes u operadores de back-office que registran préstamos, gestionan el cronograma de cuotas, revisan y aprueban/rechazan pagos informados por clientes, y administran catálogos (bancos, tipos de préstamo, franquicias, festivos, etc.) y roles/usuarios. Operan por sesión web, día a día, muchas veces desde el celular en campo (de ahí el trabajo de adaptación móvil ya hecho en Préstamos, Informes de pago, Clientes, Maestros, Roles/Usuarios).

## Product Purpose

Sistema de gestión de préstamos (`sisconpre-v2`, migración del legado Laravel 8 `prestamos_16`/`sisconpre` a Laravel 13): registra préstamos con cronograma de cuotas autogenerado (festivos/domingos configurables, recargo por mora), hace seguimiento cuota a cuota del cobro, y gestiona el flujo de "informar un pago" (cliente reporta → staff aprueba/rechaza/gestiona) contra el saldo del préstamo. Éxito = que el staff pueda ver de un vistazo el estado de la cartera y actuar sobre lo que necesita atención (cuotas vencidas, informes de pago sin resolver) sin tener que ir pantalla por pantalla.

## Positioning

Producto interno propio de GilenSoft (no un SaaS de venta externa hasta donde consta), pensado para el negocio de préstamos de la propia empresa/franquicias asociadas. Multi-tenant por "grupo de trabajo" (franquicia/país), con RBAC propio (`spatie/laravel-permission`, permisos `"<recurso>.<habilidad>"`).

## Operating Context

- Backend Laravel 13 + Inertia 3 + Vue 3.5, desplegado en un VPS Ferozo (CentOS 7 + LiteSpeed, PHP 8.3, **MySQL 5.7** — sin CTEs/window functions/functional indexes en SQL crudo) en `prestamos.gilensoft.com`. Sin Node en el servidor: `public/build/` se commitea.
- UI de negocio en `element-plus` (tablas, diálogos, formularios); shell/navegación en `reka-ui`/shadcn con Tailwind 4 y un sistema de tokens neutro (`--background`, `--foreground`, `--card`, `--muted`, `--muted-foreground`, `--border`, `--destructive`, `--accent`, definidos en `resources/css/app.css`).
- Multi-tenencia por `grupos_trabajos_user_id` (franquicia/país): existe en las tablas, pero **ninguna consulta de lectura la aplica todavía** (hueco documentado en `PLAN_MIGRACION.md` §9; se aplica sólo al crear registros). No inventar que ya está resuelto.
- Catálogos/negocio: préstamos, cuotas (`prestamos_dias`), informes de pago (`payment_reports`, con flujo de estados Pendiente → Aprobado/Rechazado/En revisión/Remitido), clientes, bancos, franquicias, festivos, tipos de préstamo/documento/registro de pago, roles y usuarios.

## Capabilities and Constraints

- RBAC real (spatie) recién migrado desde el sistema legado (que sólo pintaba menú, sin autorización real en servidor); convención de permisos `"<recurso>.<habilidad>"`.
- Ya adaptadas a móvil (tarjetas en vez de tabla, detalle en sheet/drawer): Préstamos, Informes de pago, ~15 pantallas de Maestros, Clientes, Roles, Usuarios.
- El flujo "Informar un pago" (selector de cliente, cuotas pendientes, métodos de pago, soporte adjunto) está completo y en uso desde Informes de pago y desde Préstamos (por fila, precargando el cliente del préstamo).
- Sin motor de gráficos instalado todavía (se define al construir el Dashboard).

## Brand Commitments

- Nombre de la empresa: **GilenSoft** (`gilensoft.com`). Azul de marca confirmado: `#0073C3` (`--primary-blue` en el sitio de GilenSoft), ya aplicado globalmente como color de cabecera de todos los `<el-dialog>` de la app. No hay logo/imagotipo propio de este producto todavía dentro del repo (favicon y touch-icon siguen siendo los de Laravel por defecto).

## Evidence on Hand

- `PLAN_MIGRACION.md` en la raíz del repo: historial completo de la migración, riesgos, convenciones de RBAC, estado por fase.
- Datos reales importados del sistema legado en la BD local `prestamos_gilen` (60 préstamos, 851 cuotas, 16 informes de pago, 5 clientes) — volumen chico pero real, no fixtures inventados.

## Product Principles

1. Consistencia con el sistema legado en datos y reglas de negocio (cronogramas, recargos, catálogos), pero look & feel y arquitectura nuevos (Laravel 13, RBAC real, UI propia).
2. Prioridad operativa: lo que el staff necesita **accionar** (cuotas vencidas, informes por revisar) importa más que la exhaustividad de datos mostrados.
3. Un solo sistema de color neutro en toda la app; el azul de marca se reserva para "esto es de GilenSoft/esto pide tu atención", no como decoración de fondo.
4. Cualquier SQL nuevo debe correr en MySQL 5.7 (sin CTEs ni window functions).
5. Cambios de frontend siempre pensados para escritorio y móvil desde el diseño, no como parche después.

## Accessibility & Inclusion

Sin requisito de accesibilidad formal documentado; se mantiene buen contraste de texto y foco de teclado como práctica ya aplicada (no un estándar exigido por el cliente).
