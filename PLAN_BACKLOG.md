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
| Alertar si día de inicio es domingo y no está marcado "incluir domingos" | ❌ Pendiente                        | Checkbox existe ([PrestamoFormModal.vue:235](resources/js/components/prestamos/PrestamoFormModal.vue)), no dispara aviso al generar                                                 |
| Lista de préstamos no se actualiza al registrar uno nuevo                | 🟡 Parcial — el mecanismo ya existe | `store()` hace `fetchList(1)` en `onSuccess` ([prestamos.ts:565](resources/js/stores/prestamos.ts)). Si el bug se repite, es un caso puntual a reproducir, no ausencia de la lógica |
| Informe rechazado permite seguir modificando cuando ya está procesado    | 🟡 Parcial → **ver §1.4**           | Frontend bloquea vía `finish_estatus`; backend no valida nada                                                                                                                       |

### 2.2 Guardar filtros aplicados (JSON por tipo: formulario/reporte)

❌ **No existe.** Sin tabla, sin endpoint, sin persistencia por usuario (`localStorage` solo se usa para tema claro/oscuro). Feature nueva de cero — no priorizada todavía.

### 2.3 Paginador de listado de clientes

✅ **Hecho.** `paginate(15)->withQueryString()` + `<el-pagination>` en `ClientesTable.vue`.

### 2.4 Lista de cuotas a pagar por rango de fechas (default: día actual)

❌ **No existe** pantalla dedicada con filtros de rango de fechas / cliente / estado (demoradas, próximas). El modelo `PrestamosDias` ya tiene las columnas necesarias y el Dashboard ya cuenta "cuotas vencidas" como KPI agregado, pero no hay listado navegable. No priorizada todavía.

### 2.5 Tabla de pagos en gestión

| Ítem                                                        | Estado       | Evidencia                                                                                                                                                                |
| ----------------------------------------------------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Detalle editable cuando el informe está en estado "remitir" | ❌ Pendiente | El detalle de un informe (`DetalleInformeModal.vue`) es solo lectura hoy                                                                                                 |
| Progresar remisión con confirmación aprobar/pendiente       | ❌ Pendiente | No existe esa transición. Nota: "Remitido" ya tiene `finish_estatus=1` en el seed, lo que hoy **impide** progresar desde ahí — revisar esa config si se retoma este ítem |

### 2.6 Reportes

| Ítem                                                                               | Estado       | Evidencia                                                                                                                                                                                                                     |
| ---------------------------------------------------------------------------------- | ------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Listado de préstamos agrupado por cliente/día/banco/tipo de pago/país/ciudad/grupo | ❌ No existe | No hay módulo "Reportes" separado del listado plano de `/prestamos`                                                                                                                                                           |
| Gráficas: torta por estado, utilidad, monto prestado/recibido/perdido              | 🟡 Parcial   | El Dashboard ya tiene gráficas reales con datos (`CarteraPorEstadoChart.vue`, `SerieMensualChart.vue`), pero son de barras (no torta) y no incluyen utilidad/monto perdido. Viven en el Dashboard, no en un módulo "Reportes" |

### 2.7 Tabla de transacciones (ingresos/egresos centralizados)

🟡 **Ya existe una tabla que cumple ese rol**: `customer_movement_histories` + `summary_customer_movements` (con `payment_report_id` nullable). **Decisión de arquitectura pendiente de confirmar**: si se quiere centralizar ingresos/egresos, se extiende esa tabla — `payment_reports` no es candidata porque está acoplada al dominio "informar un pago", no a movimientos genéricos.

### 2.8 Flujo de informe de pago

| Ítem                                                              | Estado                              | Evidencia                                                                                                                                                      |
| ----------------------------------------------------------------- | ----------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Bloquear selección de cuota ya vinculada a informe pendiente      | ✅ Hecho → **ver §3.3**             | Bloqueado en UI y en backend                                                                                                                                   |
| Leyenda "pendiente de confirmación" + n° comprobante              | ✅ Hecho → **ver §3.3**             | Tooltip en `InformarPagoModal.vue` con el nº de informe                                                                                                        |
| Pago de cuotas con saldo a favor                                  | ✅ Hecho                            | Completo de punta a punta                                                                                                                                      |
| Validar cliente seleccionado antes de habilitar "pagar"           | ✅ Hecho                            | `InformarPagoModal.vue` (`puedeRegistrar`)                                                                                                                     |
| Botón general vs. botón por cliente                               | ✅ Hecho                            | `abrirInformar(clienteId?)` soporta ambos casos                                                                                                                |
| Menú según permisos                                               | ✅ Hecho                            | `Menu.php` filtra por `$user->cannot($item->permission)`                                                                                                       |
| Reajuste de informe de pago                                       | 🚫 Cerrado — ver §1.1               |                                                                                                                                                                |
| Email en cada cambio de estado                                    | ❌ No existe                        | No hay ni un mailable para esto (ni siquiera al crear)                                                                                                         |
| Aprobar → marcar cuotas pagadas + saldo a cartera + transaccional | 🟡 Parcial                          | Pasa por un endpoint genérico `change_estatus_report`, sin método `aprobar()` dedicado. Sí marca cuotas pagadas y sí registra en `customer_movement_histories` |
| Rechazar → motivo + soporte + liberar cuotas                      | 🟡 Parcial — liberar ✅, soporte ❌ | La cuota ya se libera sola al rechazar (§3.3). Motivo se exige; **soporte sigue sin exigirse** (`soporte=0` en el seed de estados para Rechazado) — pendiente  |
| KPI de informes pendientes clickeable con modal                   | ❌ Pendiente                        | El número ya está en el Dashboard (`kpis.informes_por_revisar`), sin `@click` ni modal                                                                         |

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

---

## 4. No priorizado (queda en el backlog, sin fecha)

- Guardar filtros aplicados (JSON por tipo).
- Listado de cuotas a pagar por rango de fechas.
- Módulo "Reportes" (listado agrupado de préstamos).
- Gráficas de torta + utilidad/monto perdido en el Dashboard.
- Email en cada cambio de estado del informe de pago.
- Exigir soporte/comprobante al rechazar un pago.
- Método `aprobar()`/`rechazar()` dedicados en vez del genérico `change_estatus_report` (refactor, no bloquea nada funcional).
- Decisión de arquitectura sobre tabla de transacciones centralizada (§2.7).
