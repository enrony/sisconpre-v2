# Backlog de mejoras — sisconpre-v2 (`prestamos_gilen`)

> **Origen:** `TasksSISCONPRE.pdf` (lista de ideas/mejoras del usuario, sin fecha única — contiene
> anotaciones internas de 2024-05 y 2024-06 de una lista previa).
> **Fecha de triage:** 2026-09-15 — cada ítem se contrastó contra el código real (no contra lo que
> se ve en pantalla) antes de clasificarlo.
> **Cómo usar este documento:** cada ítem tiene un estado. Al terminar un ítem, actualizar su estado
> a ✅ y sumar el commit/fecha. No se agregan ítems nuevos acá sin pasar primero por el mismo triage
> (evidencia en código, no solo la idea).

Leyenda: ✅ Hecho · 🟡 Parcial · ❌ Pendiente / no existe · 🚫 Cerrado (no se hace) · 🔜 Priorizado

---

## 1. Decisiones tomadas (2026-09-15)

| #   | Tema                                                      | Decisión                                                                                                                                                                                                                                                                                                      |
| --- | --------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | "Reajuste de informe de pago"                             | 🚫 **Cerrado.** El propio análisis de negocio en el PDF concluía que no conviene alterar información ya registrada, solo aplicar saldo a favor — y el pago con saldo a favor **ya está implementado** (`PaymentReportService::registerPositiveBalance`). No se construye nada adicional.                      |
| 2   | CRUD de `modules` (menú dinámico)                         | ✅ **Corregido el diagnóstico y cerrado (2026-09-15) — no era una feature a medio construir.** `modules`/`profiles` y las 5 tablas asociadas son el RBAC **legado**, ya reemplazado por `menu_items` + spatie (`PLAN_MIGRACION.md §11` decía explícitamente eliminarlas). Se eliminó todo. Detalle en §3.1.   |
| 3   | Cuota bloqueada mientras hay un pago informado sobre ella | ✅ **Hecho (2026-09-15), como una sola feature: "estado de reserva de cuota".** Bloqueado en backend (no solo UI): `PaymentReportService::verifiedCuotasDisponibles()`. Detalle en §3.3.                                                                                                                      |
| 4   | Bloqueo de edición de informe rechazado/procesado         | ✅ **Hecho (2026-09-15).** `PaymentReportService::verifiedCurrentStatus()` ahora rechaza cualquier cambio de estado si el estado actual tiene `finish_estatus`, sin importar el estado destino. Cubre el bypass de llamar directo al endpoint. Test: `test_no_se_puede_cambiar_estado_de_informe_finalizado`. |

Estas 4 quedan como **próximas a abordar**, en el orden que se decida al arrancar cada una.

---

## 2. Backlog completo (triage 2026-09-15)

### 2.1 Novedades a corregir

| Ítem                                                                     | Estado                              | Evidencia                                                                                                                                                                           |
| ------------------------------------------------------------------------ | ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Alertar si día de inicio es domingo y no está marcado "incluir domingos" | ✅ Hecho (2026-10-01) → §3.12       | Aviso en línea bajo la fecha + bloqueo con mensaje al tocar "Generar"                                                                                                               |
| Lista de préstamos no se actualiza al registrar uno nuevo                | 🟡 Parcial — el mecanismo ya existe | `store()` hace `fetchList(1)` en `onSuccess` ([prestamos.ts:565](resources/js/stores/prestamos.ts)). Si el bug se repite, es un caso puntual a reproducir, no ausencia de la lógica |
| Informe rechazado permite seguir modificando cuando ya está procesado    | ✅ Hecho → **ver §1.4**             | Bloqueado también en backend (`verifiedCurrentStatus`)                                                                                                                              |

### 2.2 Guardar filtros aplicados (JSON por tipo: formulario/reporte)

❌ **No existe.** Sin tabla, sin endpoint, sin persistencia por usuario (`localStorage` solo se usa para tema claro/oscuro). Feature nueva de cero — no priorizada todavía.

### 2.3 Paginador de listado de clientes

✅ **Hecho.** `paginate(15)->withQueryString()` + `<el-pagination>` en `ClientesTable.vue`.

### 2.4 Lista de cuotas a pagar por rango de fechas (default: día actual)

✅ **Hecho (2026-10-01) → §3.14.** Pantalla "Cuotas pendientes": cuotas por cobrar (prestamista, su grupo) y por pagar (cliente, las suyas).

### 2.5 Tabla de pagos en gestión

| Ítem                                                        | Estado       | Evidencia                                                                                                                                                                |
| ----------------------------------------------------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Detalle editable cuando el informe está en estado "remitir" | ❌ Pendiente | El detalle de un informe (`DetalleInformeModal.vue`) es solo lectura hoy                                                                                                 |
| Progresar remisión con confirmación aprobar/pendiente       | ❌ Pendiente | No existe esa transición. Nota: "Remitido" ya tiene `finish_estatus=1` en el seed, lo que hoy **impide** progresar desde ahí — revisar esa config si se retoma este ítem |

### 2.6 Reportes

| Ítem                                                                 | Estado                | Evidencia                                                                                                                  |
| -------------------------------------------------------------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Listado de préstamos filtrable por cliente/fecha/país/ciudad/grupo   | ✅ Hecho (2026-09-16) | Módulo nuevo `/reporte_prestamos`. Ver §3.4 — banco/tipo de pago quedaron fuera a propósito, no son atributos del préstamo |
| Reporte de informes de pago recibidos, filtrable por método/tipo/etc | ✅ Hecho (2026-09-17) | Módulo nuevo `/reporte_informes_pago`. Ver §3.6                                                                            |
| Vista consolidada: KPIs + desglose por estado (ambos reportes)       | ✅ Hecho (2026-09-17) | Ver §3.7 — se resolvió como KPI tiles + barra por estado (no torta), diseño validado con el skill `dataviz`                |

### 2.7 Tabla de transacciones (ingresos/egresos centralizados)

🟡 **Ya existe una tabla que cumple ese rol**: `customer_movement_histories` + `summary_customer_movements` (con `payment_report_id` nullable). **Decisión de arquitectura pendiente de confirmar**: si se quiere centralizar ingresos/egresos, se extiende esa tabla — `payment_reports` no es candidata porque está acoplada al dominio "informar un pago", no a movimientos genéricos.

### 2.8 Flujo de informe de pago

| Ítem                                                              | Estado                                | Evidencia                                                                                                                                               |
| ----------------------------------------------------------------- | ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Bloquear selección de cuota ya vinculada a informe pendiente      | ✅ Hecho → **ver §3.3**               | Bloqueado en UI y en backend                                                                                                                            |
| Leyenda "pendiente de confirmación" + n° comprobante              | ✅ Hecho → **ver §3.3**               | Tooltip en `InformarPagoModal.vue` con el nº de informe                                                                                                 |
| Pago de cuotas con saldo a favor                                  | ✅ Hecho (2026-10-01) → **ver §3.11** | Re-triage 2026-09-30: estaba mal marcado — el saldo solo se acumulaba, no había forma de usarlo                                                         |
| Validar cliente seleccionado antes de habilitar "pagar"           | ✅ Hecho                              | `InformarPagoModal.vue` (`puedeRegistrar`)                                                                                                              |
| Botón general vs. botón por cliente                               | ✅ Hecho                              | `abrirInformar(clienteId?)` soporta ambos casos                                                                                                         |
| Menú según permisos                                               | ✅ Hecho                              | `Menu.php` filtra por `$user->cannot($item->permission)`                                                                                                |
| Reajuste de informe de pago                                       | 🚫 Cerrado — ver §1.1                 |                                                                                                                                                         |
| Email en cada cambio de estado                                    | ❌ No existe                          | No hay ni un mailable para esto (ni siquiera al crear)                                                                                                  |
| Aprobar → marcar cuotas pagadas + saldo a cartera + transaccional | 🟡 Parcial                            | Marca cuotas, descuenta/acredita saldo y ahora cierra el préstamo saldado (§3.11). Falta registrar el pago de cuotas como transacción (depende de §2.7) |
| Rechazar → motivo + soporte + liberar cuotas                      | ✅ Hecho (2026-10-01) → §3.12         | Soporte obligatorio (`soporte=1` para Rechazado) y motivo/soporte validados también en backend                                                          |
| KPI de informes pendientes clickeable con modal                   | ✅ Hecho (2026-10-01) → §3.12         | Lleva al listado filtrado (no modal); corrigió además los informes sin estado que el KPI no contaba                                                     |

---

## 3. Detalle de las features priorizadas

### 3.1 CRUD de `modules` (menú dinámico) — diagnóstico corregido y cerrado (2026-09-15)

**El triage anterior estaba mal planteado.** No era "backend completo esperando su pantalla": `modules` /
`modules_relations` / `actions` / `modules_actions` / `modules_actions_profiles` / `profiles` / `users_profiles`
son el RBAC **propio del sistema legado**, y `PLAN_MIGRACION.md §11` decía explícitamente, desde el arranque
de este proyecto: _"Tablas a eliminar tras la migración: ... (`modules`/`modules_relations` reconvertidas a
`menu_items`)"_. Esa consolidación a spatie + `menu_items` **ya estaba hecha y en uso** — `MenuItem.php` dice
en su propio docblock "Reemplaza `modules` + `modules_relations`". `ModulesController`/`ActionController`/
`ProfileController` (el de raíz, no `Admin\RolesController`) ya ni siquiera renderizaban: sus vistas Inertia
(`Modules`, `Action`, `Profiles`) nunca existieron en el frontend.

**Hallazgo importante durante la limpieza:** `Controller::obtenerPaisActivo()` — el método del que depende
**todo** el filtrado por país (Dashboard, préstamos, informes de pago, los 5 maestros) — llamaba a
`isSuperUsuario()`, que sí seguía leyendo de la tabla legada `users_profiles`/`profiles.su`. Borrar las tablas
a ciegas habría roto la detección de superusuario en toda la app. Se corrigió primero: `isSuperUsuario()`
ahora resuelve contra el rol spatie `super-admin` (ya existía, sincronizado en su momento por
`rbac:sync-from-legacy`).

**Se eliminó:**

- Controladores: `ActionController`, `ModulesController`, `ProfileController` (raíz).
- Modelos: `Action`, `Modules`, `ModulesActions`, `ModulesActionsProfiles`, `ModulesRelation`, `Profiles`,
  `ProfilesUsers` (+ sus 2 relaciones muertas en `User.php`, y `'profiles'` sacado del `$with` eager-load).
- Requests/rutas: `StoreModuleRequest`, `StoreActionRequest`, `UpdateActionRequest`, `ProfileRequest`,
  `routes/Action.php`, `routes/modules.php`, entradas `'action'`/`'modules'` del panel en `bootstrap/app.php`.
- El comando `rbac:sync-from-legacy` (su única función era leer de estas tablas; además reconstruía
  `menu_items` desde cero en cada corrida, lo que habría borrado las entradas agregadas a mano esta semana —
  quedó como una trampa, no solo como código muerto).
- Las 7 tablas (migración `drop_legacy_rbac_tables`) + el ítem de menú huérfano "Módulos" que apuntaba a la
  ruta ya eliminada, + su ícono en `NavMenu.vue`.
- `legacy:import-data` ya no intenta importar estas 7 tablas (se sacaron de su lista).

**Tests:** `test_is_super_usuario_via_rol_spatie` (regresión del fix crítico); `test_super_accede_a_los_indices_del_panel` actualizado (ya no prueba `/modules`).

### 3.2 Validar en el backend el bloqueo de edición de informes rechazados/procesados — ✅ Hecho (2026-09-15)

`PaymentReportService::verifiedCurrentStatus()` ahora carga `payment_reports_movement_first.estatus_description`
y corta con una excepción si `finish_estatus` es true, antes de siquiera mirar el estado destino. Mismo mensaje
que ya usaba el frontend ("Este informe está en un estado final y no se puede modificar."). Regresión cubierta
en `PanelSmokeTest::test_no_se_puede_cambiar_estado_de_informe_finalizado`.

### 3.3 Estado de reserva de cuota (feature unificada) — ✅ Hecho (2026-09-15)

**Objetivo del usuario:** que no se pueda informar el pago de una misma cuota más de una vez mientras esté
en algún préstamo activo con un informe en curso.

**Unificaba estos 3 ítems sueltos del backlog original**, ahora resueltos juntos:

1. Bloquear selección de cuota ya vinculada a un informe pendiente.
2. Mostrar leyenda "pendiente de confirmación" + n° de comprobante en la cuota.
3. Liberar la cuota si el informe que la reservaba es rechazado.

**Lo que se hizo:**

- **Bug de fondo corregido primero:** `PrestamosDias::pendientesPago()` era un `hasOne` sin orden — si una
  cuota tenía más de un `SelectedPaymentReport` (p. ej. un informe viejo rechazado y uno nuevo pendiente),
  podía devolver el que no correspondía. Se le agregó `->latest()`, con tipo de retorno `HasOne` explícito
  (resolvió además una entrada del baseline de PHPStan).
- **Backend, fuente de verdad:** `PaymentReportService::verifiedCuotasDisponibles()` — nuevo método, llamado
  al inicio de `PaymentReportController::store()` (dentro de la misma transacción). Rechaza el registro si
  alguna cuota enviada ya está `pagado=true` o tiene un informe vinculado cuyo último movimiento no es final
  (`finish_estatus=0`). Esto es lo que realmente lo impide — cierra el mismo tipo de bypass que §3.2.
- **"Liberar" no necesitó código propio:** como la reserva siempre se evalúa contra el _último_ movimiento
  del informe (gracias al `->latest()` de arriba), en cuanto un informe pasa a Rechazado la cuota queda
  disponible de nuevo automáticamente — no hay un paso de "liberar" separado que mantener.
- **Frontend:** `PrestamosController::transformData()` ahora expone también `payment_report_id_pendiente`
  (el nº de comprobante). `InformarPagoModal.vue` deshabilita el checkbox de la cuota reservada y muestra un
  tooltip "Pendiente de confirmación — informe #N"; `paymentReport.ts` (`toggleCuota`) también rechaza el
  toggle como defensa en profundidad.
- **Tests:** `test_no_se_puede_informar_pago_de_cuota_ya_reservada` y
  `test_cuota_se_libera_si_el_informe_que_la_reservaba_fue_rechazado`.

### 3.4 Módulo Reportes — listado de préstamos (2026-09-16)

**Decisiones tomadas antes de construir** (el pedido mezclaba dos cosas que no encajaban con el modelo de
datos): el "banco"/"tipo de pago" del PDF viven en cada pago informado, no en el préstamo — un préstamo puede
tener pagos de varios bancos — así que se dejaron fuera de este reporte. El listado quedó como **tabla
filtrable** (una fila por préstamo, no un pivot de totales). "Monto perdido" (para cuando se hagan las
gráficas) queda definido como `monto_prestamo` de los préstamos en estado Perdido.

**Qué se hizo:**

- Nuevo módulo `/reporte_prestamos` (permiso `reporte_prestamos.listar`, nadie lo tiene asignado todavía salvo
  `super-admin` vía `Gate::before` — se asigna a los roles que corresponda desde "Roles y permisos"). Cuelga
  del grupo de menú **"Reportes"**, que ya existía vacío desde el scaffold inicial.
- `ReportePrestamosController`: filtra por cliente, rango de fechas de registro, país, ciudad y grupo de
  trabajo. Aplica automáticamente el país activo del usuario (mismo criterio que `/prestamos`); el filtro de
  país solo es elegible para quien no tiene país fijo (superusuario).
- Se encontraron y corrigieron dos relaciones con bugs de FK **nunca antes usadas** (`grep` confirmó cero
  llamadas): `GruposTrabajoUser::grupo_trabajo()` apuntaba a la FK por convención (`grupos_trabajo_id`) en vez
  de la real (`idgrupo_trabajo`); se corrigió porque el reporte la necesita para resolver ciudad/grupo de cada
  préstamo. `GruposTrabajo::grupo_trabajo_user()` tiene el mismo tipo de problema pero sigue sin ningún
  llamador — se le agregó el tipo de retorno de paso, no se tocó su FK (fuera de alcance, cero impacto hoy).
- Nota de datos (no es un bug de este código, es preexistente): algunos préstamos antiguos tienen
  `country_id` propio que no coincide con el país de la ciudad de su grupo de trabajo — el reporte muestra
  ambos datos tal como están, no intenta reconciliarlos.
- Frontend: `ReportePrestamos.vue` + `ReportePrestamosFilters.vue`/`ReportePrestamosTable.vue` (Pinia store
  `reportePrestamos.ts`), mismo patrón que la pantalla de Informes de pago (filtros + `ResponsiveList`).
- **Tests:** `test_reporte_prestamos_permission_gate`, `test_reporte_prestamos_filtra_por_pais_activo`,
  `test_reporte_prestamos_tables`.

### 3.5 Reportes: filtros en cascada + Excel/PDF/Imprimir, reutilizable (2026-09-16)

Ampliación pedida sobre §3.4, explícitamente para que sirva de base a **todos los reportes futuros**, no solo
este.

**Filtros (cascada), ahora compartidos en `ReporteFiltrosController` + `routes/shared.php`** (no en
`ReportePrestamosController`, para no reescribir la cascada en cada reporte nuevo):

- `estados` y `grupos` pasaron a selección múltiple; `responsables` es nuevo (multi + autocomplete).
- País → ciudad: sin país, la ciudad **no ofrece ninguna opción** (a propósito, tal como se pidió).
- País → grupo: sin país, muestra **todos** los grupos del alcance del usuario; con país, solo los de ese país.
- Grupo → responsable: el autocomplete de responsables se acota a los grupos elegidos (o a todo el alcance si
  no se eligió ninguno).
- Se agregó `GruposTrabajoUser::user()` (faltaba) para resolver quién es el responsable de cada préstamo (el
  `iduser` del `grupos_trabajos_users` que tenía en el momento del registro).
- El catálogo de estados se sirve desde el propio módulo de reportes (`/reportes/filtros/estados-prestamo`) y
  no desde `/prestamos/recordsEstados`, a propósito: ese último está gateado por `prestamos.listar`, y alguien
  con solo `reporte_prestamos.listar` se hubiera quedado sin poder cargar el filtro.

**Exportar/Imprimir, reutilizable por cualquier reporte futuro:**

- Librerías nuevas: `phpoffice/phpspreadsheet` (Excel) y `barryvdh/laravel-dompdf` (PDF) — confirmadas con el
  usuario antes de instalar. **Pendiente de verificar en el servidor Ferozo**: `phpspreadsheet` necesita la
  extensión `ext-zip` de PHP para generar `.xlsx` (es un archivo zip); no hay forma de confirmar esto sin
  acceso al server — probarlo ahí antes de asumir que el Excel va a funcionar en producción. Dompdf es PHP
  puro, no debería tener este problema.
- `app/Http/Controllers/Concerns/ExportsReport.php`: trait con `generarExcel()`/`generarPdf()`, genérico —
  recibe filas ya "aplanadas" (un array asociativo por fila, sin objetos anidados) y un mapa
  `clave => etiqueta`. Cualquier `Reporte*Controller` nuevo solo necesita `use ExportsReport;` y armar su
  propia query sin paginar.
- `resources/views/reportes/pdf.blade.php`: plantilla PDF genérica (tabla + título), reutilizable igual.
- "Imprimir" no generó código nuevo aparte: abre el mismo PDF pero con `?inline=1` (`Pdf::stream()` en vez de
  `Pdf::download()`), y el usuario imprime desde el visor de PDF del navegador — evita mantener una vista
  "imprimible" HTML aparte.
- Frontend: `ReporteToolbar.vue` (botones Excel/PDF/Imprimir) recibe las 3 URLs ya armadas — no sabe nada del
  reporte en sí, cualquier página de reporte nueva lo puede reusar tal cual.
- **Verificado con datos reales** (vía tinker, no solo el navegador): el `.xlsx` generado se releyó con
  PhpSpreadsheet y trajo las filas correctas; el PDF se abrió y se revisó visualmente (cabecera azul de marca,
  filas alternadas, filtrado correcto).
- **Tests:** `test_reportes_filtros_en_cascada`, `test_reporte_prestamos_exportar`.

### 3.6 Reporte de informes de pago recibidos (2026-09-17)

Segundo consumidor de la infraestructura de §3.4/§3.5 (prueba de que efectivamente sirve para "todos los
reportes", no solo préstamos) — filtros y export/imprimir se reusaron sin cambios.

**Antes de construir se aclaró un hallazgo real:** el pedido era filtrar por "método y tipo de pago". Existe
una tabla `type_payment_record` que parecía ser "tipo de pago", pero en la práctica **todos** los informes
(60+) tienen el mismo `type_payment_record_id` — se hardcodea en `PaymentReportService::createPaymentReport()`
(`TypePaymentRecord::first()->id`), nadie lo elige nunca. Filtrar por eso hoy no distingue nada. Lo que sí
varía de verdad es `destination` (Pago de cuotas / Saldo a favor, ya usado como "Destino del pago" en la
consola operativa) — el usuario confirmó que a eso se refería. También se sumó "banco" (vive en la misma
tabla que método de pago, mismo esfuerzo).

**Filtros:** cliente, rango de fechas, estado del informe (multi), destino (multi), método de pago (multi),
banco (multi), país (mismo criterio indirecto ya probado: cuotas → préstamo → `country_id`; un informe de
saldo a favor sin cuotas queda siempre visible), ciudad/grupo/responsable (derivados del `grupos_trabajos_user_id`
**del informe**, no del préstamo — quién lo gestionaba al registrarse, mismo patrón que §3.4).

**Relaciones nuevas** (`PaymentReport::grupoTrabajoUser()`, `paymentReportsMethod::paymentMethod()`/`bank()`) —
ninguna existía todavía en ese modelo. De paso se tipó `PaymentReport::payment_reports_methods()` (retorno sin
tipo, rompía el `whereHas` que necesita este reporte) y se agregó `@property-read $created` al docblock del
modelo (mismo patrón que `Prestamos.php`), resolviendo 2 entradas más del baseline de PHPStan.

**Catálogo de estados** servido igual que en §3.4: desde el propio módulo de reportes
(`/reportes/filtros/estados-informe-pago`), no desde el endpoint de la consola operativa — mismo motivo (evitar
acoplar el permiso de un reporte al de otro módulo).

**Catálogos de método/banco:** se reusaron los endpoints transversales ya existentes
(`/payment_methods/tables`, `/banks/tables/{pais?}`) — no hizo falta agregar nada nuevo al controlador
compartido de filtros para esto.

**Tests:** `test_reporte_informes_pago_permission_gate`, `test_reporte_informes_pago_filtros`,
`test_reporte_informes_pago_estados_catalogo`, `test_reporte_informes_pago_exportar`.

### 3.7 Vista consolidada (KPIs + desglose por estado) en ambos reportes (2026-09-17)

Cierra el ítem "Gráficas" que había quedado sin priorizar en §2.6/§4 — con diseño validado con el skill
`dataviz` en vez de a ojo.

**Decisión de forma (antes de tocar color):** ni torta ni gráfico de más ejes. Los totales (cantidad, montos)
son "un puñado de cifras encabezado" → **KPI tiles**; el desglose por estado es un **status job** (escala fija,
reservada, siempre ícono+etiqueta), no una identidad categórica → **barra horizontal con los mismos 4 tipos
semánticos que ya usa el Dashboard** (info/success/warning/danger), no una paleta nueva. "Por destino" en
informes de pago son solo 2 categorías nominales → se resolvió como 2 tiles de KPI más, no un gráfico aparte
(evita el anti-patrón de "torta de 2 porciones").

**Reutilización, no duplicación:** `ReporteEstadoBar.vue` es una generalización de
`dashboard/CarteraPorEstadoChart.vue` (mismo layout de barra, mismos 4 colores/íconos por tipo semántico) para
no atarse a los 4 estados puntuales de préstamos — cualquier catálogo de estados que sepa resolver su propio
`tipo` puede reusarla. Los informes de pago no tienen un `type_tag` como préstamos (solo un color legado tipo
`text-green-400`); se resuelve con un mapeo chico en el backend (`tipoDesdeStyle()`) en vez de inventar un
campo nuevo.

**Backend:** cada controlador se separó en `filteredQuery()` (todos los `when()` de filtro, sin columnas) +
`baseQuery()` (agrega columnas/eager-loads del listado) — así el nuevo `resumen()` reusa exactamente los
mismos filtros sin duplicar la lógica, agregando encima solo lo que necesita (agregados SQL con `clone` +
`toBase()` en préstamos; colección en PHP para informes, por la complejidad de agrupar por el último
movimiento vía relación `hasOne`). "Monto perdido" quedó finalmente resuelto con datos reales (no solo
definido en el papel, ver §1).

**Dónde vive:** un toggle "Detallado / Consolidado" (`el-radio-button`, mismo componente ya usado en
"Informar un pago" para Destino del pago) dentro de la misma pantalla — los mismos filtros aplican a las dos
vistas, no hay que duplicar la barra de filtros.

**Verificado con datos reales** (tinker) y visualmente (fixture con datos mockeados, "Monto perdido" se lee
como alerta real — ícono + texto rojo, no solo color).

**Tests:** `test_reporte_prestamos_resumen`, `test_reporte_informes_pago_resumen`.

### 3.8 Imprimir cartón de pagos del préstamo (2026-09-17)

Funcionalidad **nueva**, no migración: en `prestamos_16` no existía nada equivalente (se buscó "carton",
"carnet", "imprimir", "pdf" en el legado y no hay resultado — el usuario adjuntó una plantilla en blanco tipo
tarjeta como referencia de lo que se espera, no un fixture a portar).

**Dónde vive:** no hay pantalla de detalle con URL propia — el "detalle" es el `PrestamoDetalleSheet.vue` que
se abre desde `PrestamosTable.vue`. Se agregó la acción "Imprimir cartón" en tres puntos: el dropdown
"Acciones" de la fila (desktop), el botón de acciones de `ResponsiveList` (mobile) y un ícono en el header del
Sheet — mismo patrón `window.open(url, '_blank')` que ya usa `ReporteToolbar.vue` para "Imprimir".

**Backend:** `PrestamosController::imprimirCarton()` + `GET /prestamos/{prestamo}/carton` (mismo gate
`prestamos.listar` del resto del módulo, vía el `$panel` map — sin middleware propio). Vista Blade nueva
`resources/views/prestamos/carton.blade.php` (no la genérica de `reportes/pdf.blade.php`, que es tabular pura;
esta tiene secciones cliente/préstamo + tabla). La columna **Resta** es el saldo _planificado_ según el
cronograma (`total` menos la suma acumulada de cuotas con `apply=true` en orden de fecha) — no el saldo real
post-pago, que ya se ve aparte en **Estado** (Pagada/Vencida/Pendiente/No aplica) por cuota. El dato de
cliente (dirección, teléfono) sale de `datoCliente` (tabla `clientes`), no del snapshot JSON `prestamos.cliente`
que sólo tiene nombre/apellido/documento.

**Diseño:** cabecera con el azul de marca (`#0073C3`), no la plantilla en blanco de la referencia — con
identidad GilenSoft, secciones de datos y badge de estado con los mismos 4 colores semánticos del resto de la
app. Diseñado y aprobado con el usuario antes de implementar (impeccable, refinamiento acotado — no ameritó el
flujo completo de "nuevo mundo visual" al reutilizar el lenguaje visual ya establecido).

**Verificado** generando el PDF real (tinker, préstamo #30, cliente con dirección/teléfono reales) y leyéndolo
directamente — sin servidor http disponible para probarlo autenticado en el browser pane.

**Tests:** `test_prestamos_imprimir_carton`.

### 3.9 Insignia de Estado en las tarjetas móviles de ambos reportes (2026-09-17)

El usuario reportó (captura de `/reporte_prestamos` en mobile) que no se veían "botones de acción" en las
tarjetas. Diagnóstico con `impeccable`: no es un bug — `ReportePrestamosTable.vue`/`ReporteInformesPagoTable.vue`
nunca definieron el slot `#actions` de `ResponsiveList.vue` (a diferencia de `PrestamosTable.vue`, que sí lo usa
para "Ver detalle"/"Informar un pago"/"Imprimir cartón"), porque son reportes de solo lectura/exportación, sin
acciones por fila ni en su tabla de escritorio. **Decisión confirmada con el usuario: se mantiene así a
propósito**, no se agregan acciones.

Lo que sí era una inconsistencia real: "Estado" se veía como texto plano gris en la tarjeta móvil, mientras que
en escritorio es un `<el-tag>` (chip). Se movió "Estado" del arreglo `fields` genérico al slot `#badge` de
`ResponsiveList` (mismo lugar que usa el chip de ID en `PrestamosTable.vue`), en los dos reportes por igual.
Verificado con un fixture descartable (viewport 375px, requiere `<meta name="viewport">` explícito en el HTML
del fixture — sin eso el navegador emulado renderiza a 980px de todos modos) — insignia visible junto al título,
igual que en escritorio.

### 3.10 Aprobación de préstamo — no se puede informar un pago sobre uno no aprobado (2026-09-18)

Pedido del usuario: "no se puede aplicar un informe de pago a un préstamo que no esté aprobado". Investigación
previa (sin tocar código) encontró un conflicto real: `prestamos.estatus` (catálogo `prestamos_estatus`, 4
filas: Pendiente/Pagado/Anulado/Perdido) ya usa "Pendiente" con otro significado — préstamo **en curso de
cobro**, no "pendiente de aprobación" — y lo consumen Dashboard/reportes/filtros. Reutilizarlo habría roto esas
tres pantallas. Tampoco existía ningún flujo de aprobación de préstamos, ni en este sistema ni en el legado
`prestamos_16` — es feature nueva, confirmado con el usuario junto con el resto del diseño antes de escribir
código:

- **Eje de datos separado**: nueva tabla/catálogo `prestamos_aprobacion_estatus` (3 filas fijas: Pendiente de
  aprobación=1/info, Aprobado=2/success, Rechazado=3/danger) + columna `prestamos.aprobacion_estatus_id`
  (constantes `Prestamos::APROBACION_*`). No toca `estatus` para nada.
- **Datos existentes**: los ~60 préstamos ya en producción se migran automáticamente a "Aprobado" — la regla
  nueva solo aplica hacia adelante. Todo préstamo creado desde ahora nace "Pendiente de aprobación"
  (`PrestamosController::store()`).
- **Aprobar/rechazar**: `PUT /prestamos/{id}/aprobar|rechazar`, gateado por el permiso nuevo `prestamos.aprobar`
  (nadie lo tiene asignado todavía — se otorga desde Roles y permisos). Botones en el dropdown de Acciones y en
  las tarjetas móviles de `/prestamos`, visibles según el estado actual (no se ofrece "Aprobar" si ya está
  aprobado, ni "Rechazar" si ya está rechazado). Columna/insignia "Aprobación" nueva en la tabla — solo se
  resalta en la tarjeta móvil cuando NO está aprobado (evita ruido en el caso normal).
- **Validación real (defensa en profundidad, dos capas)**: 1) `Prestamos::scopePrestamoActivo()` — usado por
  "Informar un pago" para buscar préstamos activos del cliente — ahora exige `aprobacion_estatus_id = Aprobado`,
  así que un préstamo no aprobado ni siquiera aparece como opción; 2) `PaymentReportService::verifiedPrestamosAprobados()`
  bloquea también el llamado directo al endpoint (mismo patrón que `verifiedCuotasDisponibles()`). No aplica al
  "saldo a favor" (sin cuotas, sin préstamo referenciado) — un informe de pago puede además referenciar cuotas
  de más de un préstamo a la vez (relación indirecta vía `selected_payment_reports → PrestamosDias → Prestamo`).

**Tests:** `test_prestamo_nuevo_nace_pendiente_de_aprobacion`, `test_prestamos_aprobar_rechazar`,
`test_no_se_puede_informar_pago_de_prestamo_no_aprobado`, `test_informar_pago_no_ofrece_prestamos_no_aprobados`.

---

### 3.11 Ciclo de vida del préstamo + pago de cuotas con saldo a favor (2026-10-01)

Sale del re-triage del 2026-09-30 (lista de tareas SISCONPRE en JSON, contrastada contra el código y la BD):

- **Hallazgo A:** nada pasaba un préstamo a "Pagado" al saldarse, ni había forma de anularlo o darlo por
  perdido desde la app (el préstamo #42 tenía todas sus cuotas pagadas y seguía "Pendiente").
- **Saldo a favor:** §2.8 lo daba por hecho, pero solo se acumulaba (4 movimientos, todos "Abono"): no se
  podía usar ni se veía en ninguna pantalla. El cliente #1 tenía 336.000 que no podía usar.

Reglas confirmadas con el usuario antes de implementar:

- **Cierre automático:** al aprobar un informe, todo préstamo cuyas cuotas `apply` quedan todas pagadas pasa
  a "Pagado" (`PrestamoEstadoService::cerrarSiSaldado()`). La migración corrigió el #42.
- **Anular:** solo en curso, sin cuotas pagadas ni informes en curso (= se registró por error). Final.
- **Perdido:** solo en curso y aprobado, sin informes en curso. **Reversible** ("Reactivar" → vuelve a
  "Pendiente").
- **Motivo obligatorio** al anular y al dar por perdido (opcional al reactivar). Cada cambio de estado queda en
  `prestamos_estatus_historial` (anterior, nuevo, motivo, usuario, fecha).
- **Permiso nuevo `prestamos.cambiar-estado`**: no está asignado a ningún rol; se otorga desde Roles y permisos.
- Se sincronizan los flags legados `pagado`/`anulado`/`perdido`, porque `scopePrestamoActivo()` todavía los
  consulta.
- Al **aprobar** un informe se exige que sus préstamos sigan en curso y aprobados: no se aprueba un pago sobre
  un préstamo anulado o perdido entre medio. Aprobar/rechazar el préstamo también se limita a préstamos en
  curso. En la UI, las acciones de aprobación y de recargo se ocultan en préstamos finalizados.
- Validación agregada de paso: las cuotas informadas tienen que ser de préstamos **del mismo cliente** del
  informe; antes el backend no lo verificaba, y con saldo a favor eso habría permitido gastar el saldo de un
  cliente en cuotas de otro.

**Pago de cuotas: dos modalidades, sin mezcla** (definidas por el usuario el 2026-10-01). Reemplaza el primer
diseño de este mismo ítem, que permitía mezclar saldo y dinero con aprobación diferida:

| Modalidad             | Regla                                                                                                                                  | Aprobación                                                                   |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| **Con dinero**        | Lo informado **no puede ser menor** que las cuotas elegidas (se cierra el hueco del "faltante"); el excedente queda como saldo a favor | Gestión normal (Pendiente → Aprobado/Rechazado)                              |
| **Con saldo a favor** | Sin métodos de pago; las cuotas elegidas **no pueden superar** el saldo disponible                                                     | **Inmediata**: nace "Aprobado", cuotas pagadas y saldo descontado en el acto |

- Backend: `PaymentReportService::verifiedImportes()` (los montos de las cuotas se toman de la BD, no del
  front), `registerMovimientoInicial()` y `aprobarPagoConSaldo()`, que reusa `processReportPaymentAproved()`:
  marca las cuotas, descuenta el saldo con un movimiento "Pago" (tipo 01) y cierra el préstamo si quedó saldado.
  La aplicación queda guardada en `payment_reports.saldo_favor_aplicado` y no suma a "monto recibido" en los
  reportes, porque no es dinero nuevo.
- La aprobación inmediata la puede hacer quien tenga permiso para informar pagos: ese dinero ya se verificó
  cuando entró como saldo a favor. La pantalla pide confirmación porque no se puede deshacer.
- UI: selector "¿Cómo paga?" (solo si el cliente tiene saldo disponible). En modo saldo se ocultan los métodos de
  pago y se bloquean las cuotas que ya no entran en el saldo; en modo dinero, el botón no se habilita mientras
  falte plata. El destino "Saldo a favor" pasó a llamarse **"Abonar a saldo a favor"**, para no confundirlo con
  pagar con saldo.
- La reserva de saldo por informes en curso (`SaldoFavorService`) se mantiene como resguardo, aunque con
  aprobación inmediata en la práctica queda en 0.

**Tests:** `test_pago_con_dinero_no_puede_ser_menor_que_las_cuotas`,
`test_pago_con_saldo_a_favor_y_cierre_automatico_del_prestamo`, `test_no_se_informan_cuotas_de_otro_cliente`,
`test_anular_perdido_y_reactivar_prestamo`.

---

### 3.12 Arreglos rápidos del re-triage (2026-10-01)

- **Soporte obligatorio al rechazar un pago:** era un dato de configuración (`soporte=0` en el estado
  "Rechazado"); la pantalla ya pedía el soporte según ese flag. Además, `PaymentReportService::verifiedMotivoYSoporte()`
  exige motivo y soporte según el estado destino también en el backend (hallazgo B del re-triage).
- **Primer pago en domingo:** si la 1ª cuota cae domingo y el préstamo no incluye domingos, se avisa debajo de la
  fecha y "Generar" se bloquea con un mensaje que indica cómo resolverlo.
- **KPI "Informes por revisar" clickeable:** lleva a `/payment_report?estados=1,4` (los mismos estados que cuenta
  el KPI), con el filtro ya aplicado. Solo es clickeable con `payment_report.listar`. Se resolvió con navegación al
  listado en lugar del modal que proponía la tarea original: el listado ya tiene la gestión completa.
    - **Bug encontrado y corregido en el camino:** los pagos informados con dinero nacían sin estado en el
      encabezado (`payment_reports_movements_estatus_id` nulo; solo se guardaba el movimiento), así que el KPI y el
      filtro por estado no los veían: había 4 "Pendiente" invisibles. Ahora el estado inicial se guarda siempre
      (`registerMovimientoInicial()`), y la migración completó los nulos con el estado de su último movimiento.
- **Nuevo préstamo con el cliente ya elegido:** acción "Nuevo préstamo" en la lista de clientes (con
  `prestamos.registrar`), que abre el asistente directo en el paso 2. Va por `/prestamos?nuevo=<cliente>`; el
  parámetro se borra de la URL después de abrir, para que recargar la página no lo reabra.

**Tests:** `test_rechazar_pago_exige_motivo_y_soporte`, `test_kpi_informes_por_revisar_coincide_con_el_listado`.

---

### 3.13 Alcance de la cartera por grupo / por cliente (2026-10-01)

Previo a la pantalla de cuotas (§3.14), que la necesita. Al analizarla se encontró que **un usuario cliente ya
podía ver los préstamos e informes de pago de todos los clientes de su país** (su rol tiene `prestamos.listar` y
`payment_report.listar`, y los listados solo filtraban por país), además de la lista de clientes completa en
los selectores. Decisiones del usuario: vínculo usuario↔cliente por email como vínculo fijo, alcance por
permiso, y la misma regla en todas las pantallas.

- **`App\Services\AlcanceCartera`**, una sola regla para todo:
    - super-usuario → todo;
    - `cartera.ver-grupo` → lo registrado por cualquier miembro de sus grupos de trabajo (uno o varios);
    - usuario vinculado a una ficha → solo lo suyo;
    - cualquier otro → nada.

    Se suma al filtro por país que ya existía. Se aplica en: listado de préstamos y sus acciones (cartón,
    aprobar, anular, perdido, reactivar, pausar recargo), alta de préstamo (el cliente tiene que estar en la
    cartera), informes de pago (listado, detalle, gestión, informar un pago), clientes (listado, selectores,
    ficha, edición, baja), saldo a favor, Dashboard y los dos reportes.

- **Vínculo usuario↔cliente:** `clientes.user_id` (único, no asignable masivamente). La migración lo completó
  por email cuando identifica a una sola ficha. Además se vincula solo al registrarse un usuario o al
  crear/editar una ficha con su email, y a mano desde **Usuarios** (nueva columna "Cliente vinculado").
- **Permiso `cartera.ver-grupo`:** la migración se lo asignó a "Prestamista V", "Prestamista S/V",
  "Administrador" y "Super Usuario", para que el personal no pierda visibilidad al desplegar. Otros roles de
  personal (p. ej. "Inversionista …") lo necesitan asignado a mano.
- **Endpoints legados eliminados:** `listaClientes2` (devolvía _todos_ los clientes sin filtro) y
  `consultaPrestamos` (tenía un `dd()` que volcaba datos). Ninguna pantalla los usaba.
- **Bug de diseño corregido durante los tests:** el servicio se inyecta en controladores que Laravel cachea en
  la ruta; con memoria por instancia, un segundo usuario en el mismo proceso heredaba el alcance del primero.
  La memoria quedó atada al usuario autenticado.
- **Corregido de paso:** el listado de informes no enviaba `saldo_favor_aplicado` (de §3.11), así que el
  "+ X saldo" nunca se mostraba.
- Burn-down: 7 entradas del baseline de PHPStan resueltas y eliminadas.

**Tests:** `test_cliente_solo_ve_su_cartera`, `test_prestamista_ve_su_grupo_y_sin_alcance_no_ve_nada`,
`test_vincular_ficha_de_cliente_desde_usuarios`; varios tests existentes ajustados porque usaban a la usuaria
cliente como "usuario normal de Colombia" (ahora usan un prestamista temporal del grupo).

---

### 3.14 Cuotas pendientes: por cobrar / por pagar (2026-10-01)

Definido por el usuario: una sola pantalla que le sirve al **prestamista** (qué cobros tiene que hacer, de
todos los grupos a los que pertenece) y al **cliente**, que también entra al sistema (qué cuotas debe pagar).
Se apoya en el alcance de §3.13.

- Ruta `/cuotas` (módulo `cuotas` en el `$panel` map, permiso `cuotas.listar`). Ítem de menú **"Cuotas
  pendientes"** en Procesos, con un nombre neutro porque lo ven los dos tipos de usuario. El permiso se
  asignó a los roles de personal y a "Cliente Verficado" / "Cliente S/V".
- **Qué cuenta:** cuotas cobrables (`apply`), no pagadas, de préstamos en curso y aprobados. Por defecto
  muestra las de **hoy más todas las vencidas** ("demoradas"). Filtros: rango de fechas (con atajos Hoy /
  Próximos 7 días / Este mes), "Incluir vencidas anteriores" y clientes (solo el personal). Totales del filtro:
  vencido / vence hoy / próximas.
- Una cuota con un **pago informado sin resolver** sigue en la lista, marcada "En revisión #N" y sin el botón
  de informar (no se puede informar dos veces).
- **Prestamista:** cliente, teléfono (con enlace `tel:` y botón "Llamar" en el celular), dirección, préstamo,
  "Informar un pago". **Cliente:** título "Mis cuotas por pagar", la fecha de vencimiento como encabezado de la
  tarjeta e "Informar un pago" para las suyas. El personal opera en su país activo; al cliente **no** se le
  aplica el filtro de país (sus cuotas son suyas sin importar el país del préstamo).
- En el celular, los totales se muestran como una franja compacta, para que la lista quede a la vista.
- Arreglado de paso en `ResponsiveList` (afecta a todas las pantallas): el segundo botón de acción quedaba
  corrido a la derecha al bajar de línea, por el margen que element-plus le pone a botones contiguos.

**Tests:** `test_cuotas_pendientes_filtros_y_en_revision`, `test_cuotas_pendientes_cliente_ve_solo_las_suyas`.

**Resuelto en §3.15:** el usuario definió que todo (también para el cliente) se filtra por el grupo activo y su
país, así que se quitó la excepción de país de esta pantalla.

### 3.15 Grupo de trabajo activo + selector con bandera (2026-10-01)

Pedido del usuario: tanto el cliente como el prestamista pueden pertenecer a varios grupos y alternar entre
ellos; **toda** la información se filtra por el grupo seleccionado, que se muestra siempre (con la bandera de su
país). Es la entrega 1 de 3 (2: solicitudes para unirse con aprobación del dueño y correo; 3: tarjetas "Mis
grupos" en el Dashboard).

- **`App\Services\GrupoActivo`:** el grupo activo es la membresía con `current_grupo = 1` (la columna ya existía,
  pero no había forma de cambiarla). El **super-usuario** tiene además "Todos los grupos": es su estado por
  defecto, como antes del selector, y se guarda en la sesión porque no corresponde a ninguna membresía.
- **`AlcanceCartera`** ahora acota al **grupo activo**: el personal ve lo de ese grupo, el cliente lo suyo
  dentro de ese grupo, y el super-usuario todo o el grupo que elija. `obtenerPaisActivo()` sale del mismo grupo
  (también para el super-usuario cuando eligió uno), así que el país queda alineado en todas las pantallas.
- **Selector** en el encabezado (`GrupoSwitcher`): bandera, nombre y ciudad del grupo activo; el menú lista los
  grupos con su ciudad y país. Al cambiar se recarga la página, porque los listados viven en stores que no se
  enteran del cambio. `PUT /grupo-activo` solo activa membresías propias; "Todos" solo para el super-usuario.
- **Banderas en SVG** (`BanderaPais`), no emoji: en Windows los emoji de bandera se ven como letras. Están
  dibujadas CO, VE y AR (los países activos); otro país cae al código en un chip.
- **Bugs encontrados por los tests y corregidos:**
    - Al elegir el grupo que ya era el activo, el usuario quedaba **sin grupo**: el modelo leído antes del
      update masivo "no veía cambios" y no guardaba. Ahora las dos escrituras van por query.
    - La memoria de `AlcanceCartera` sobrevivía entre requests (controlador cacheado en la ruta) y no se
      enteraba del cambio de grupo; además `spl_object_id()` recicla ids. Ahora vale por request, con una marca
      única guardada en el propio request.
    - Tests que se cortaron a mitad de camino durante la corrección dejaron datos de prueba en la BD local
      (4 informes, un movimiento "QA finalizado" en el informe #27); se limpiaron, y el test nuevo restaura el
      grupo activo del super-usuario en un `finally`.

**Test:** `test_cambiar_de_grupo_activo`.

### 3.16 Unirse a otro grupo con aprobación del dueño + aviso por correo (2026-10-01)

Entrega 2 de 3. Decisión del usuario: sumarse a otro grupo **requiere que lo apruebe el dueño del grupo**, con
aviso por correo; **WhatsApp más adelante**.

- **"Unirme a otro grupo con un código"** en el selector del encabezado: se ingresa el mismo código que se usa
  al registrarse y queda una **solicitud** (`grupos_trabajos_solicitudes`: pendiente / aprobada / rechazada,
  quién decidió y cuándo). No se puede pedir un grupo del que ya es miembro, ni repetir una solicitud
  pendiente. El código no distingue mayúsculas. Las solicitudes enviadas aparecen en el selector como
  "Esperando aprobación".
- **Quién decide:** el dueño del grupo (`grupos_trabajos.grupos_trabajos_user_id`); si el grupo no tiene dueño
  (hoy "Yulio" y "Prueba"), los super-usuarios. Lo resuelven desde el **Dashboard** (panel "Solicitudes para
  unirse a sus grupos", solo visible si hay alguna). Al aprobar se crea la membresía, o se reactiva si ya existía
  inactiva. Si el solicitante no tenía grupo activo, este pasa a serlo.
- **Correos** con Laravel Notifications (`SolicitudUnionGrupo` al dueño, `SolicitudUnionGrupoResuelta` al
  solicitante), canal `mail`. **Para WhatsApp alcanza con sumar el canal en `via()`**. Si el correo falla no
  traba la solicitud: queda registrado en el log. Se agregó `lang/es.json` porque la plantilla de correo de
  Laravel salía con textos en inglés.
- Detalle corregido en la revisión visual: el ejemplo del campo de código era un código real (el del grupo
  General); se cambió por uno genérico.

**Para decidir:** el **registro** de un usuario nuevo con código sigue entrando al grupo directo, sin aprobación.

**Test:** `test_solicitudes_para_unirse_a_un_grupo`.

### 3.17 "Mis grupos" en el Dashboard (2026-10-01)

Entrega 3 de 3. Una tarjeta por cada grupo del usuario, no solo el activo, para ver cómo está cada uno sin
cambiar de grupo. Cada tarjeta lleva la bandera, el grupo, la ciudad y el país. El grupo activo va resaltado en
azul ("Grupo actual"); las otras tarjetas tienen **"Cambiar a este grupo"**. El super-usuario en
"Todos los grupos" no tiene ninguna tarjeta resaltada.

- **Personal:** cartera activa (monto y préstamos en curso), a cobrar hoy, vencido, pagos por revisar y préstamos
  por aprobar del grupo.
- **Cliente:** solo lo suyo en ese grupo: saldo pendiente, préstamos activos, próxima cuota (monto y
  vencimiento), cuotas vencidas y pagos en revisión.
- Se calcula en el momento (`App\Services\ResumenGrupos`), con el mismo criterio que el resto: lo registrado por
  los miembros del grupo, en el país del grupo.
- Los préstamos activos del cliente se cuentan desde `prestamos` y no desde las cuotas, porque hay préstamos
  importados del legado sin cronograma de cuotas.

**Test:** `test_tarjetas_mis_grupos_del_dashboard`.

**Siguiente etapa:** campanita de notificaciones junto con los correos en cada cambio de estado; WhatsApp como
canal adicional de las mismas notificaciones.

---

## 4. No priorizado (queda en el backlog, sin fecha)

- Guardar filtros aplicados (JSON por tipo).
- Email en cada cambio de estado del informe de pago (junto con la campanita de notificaciones, §3.17).
- Método `aprobar()`/`rechazar()` dedicados en vez del genérico `change_estatus_report` (refactor, no bloquea nada funcional).
- Decisión de arquitectura sobre tabla de transacciones centralizada (§2.7).
