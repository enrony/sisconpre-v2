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

| # | Tema | Decisión |
|---|------|----------|
| 1 | "Reajuste de informe de pago" | 🚫 **Cerrado.** El propio análisis de negocio en el PDF concluía que no conviene alterar información ya registrada, solo aplicar saldo a favor — y el pago con saldo a favor **ya está implementado** (`PaymentReportService::registerPositiveBalance`). No se construye nada adicional. |
| 2 | CRUD de `modules` (menú dinámico) | 🔜 **Se retoma.** Ya existe el backend completo (`ModulesController`, rutas en `routes/modules.php`, tabla `modules`) pero está desconectado — sin pantalla en el frontend y sin que `Menu.php` lo use. Se construye el frontend y se conecta como fuente del menú. |
| 3 | Cuota bloqueada mientras hay un pago informado sobre ella | 🔜 **Se trata como una sola feature: "estado de reserva de cuota".** Objetivo del usuario: *que no se pueda informar el pago de una cuota más de una vez mientras esté en algún préstamo activo con un informe en curso.* Unifica 3 ítems que antes estaban sueltos en el backlog (ver §3.3). |
| 4 | Bloqueo de edición de informe rechazado/procesado | ✅ **Hecho (2026-09-15).** `PaymentReportService::verifiedCurrentStatus()` ahora rechaza cualquier cambio de estado si el estado actual tiene `finish_estatus`, sin importar el estado destino. Cubre el bypass de llamar directo al endpoint. Test: `test_no_se_puede_cambiar_estado_de_informe_finalizado`. |

Estas 4 quedan como **próximas a abordar**, en el orden que se decida al arrancar cada una.

---

## 2. Backlog completo (triage 2026-09-15)

### 2.1 Novedades a corregir

| Ítem | Estado | Evidencia |
|---|---|---|
| Alertar si día de inicio es domingo y no está marcado "incluir domingos" | ❌ Pendiente | Checkbox existe ([PrestamoFormModal.vue:235](resources/js/components/prestamos/PrestamoFormModal.vue)), no dispara aviso al generar |
| Lista de préstamos no se actualiza al registrar uno nuevo | 🟡 Parcial — el mecanismo ya existe | `store()` hace `fetchList(1)` en `onSuccess` ([prestamos.ts:565](resources/js/stores/prestamos.ts)). Si el bug se repite, es un caso puntual a reproducir, no ausencia de la lógica |
| Informe rechazado permite seguir modificando cuando ya está procesado | 🟡 Parcial → **ver §1.4** | Frontend bloquea vía `finish_estatus`; backend no valida nada |

### 2.2 Guardar filtros aplicados (JSON por tipo: formulario/reporte)

❌ **No existe.** Sin tabla, sin endpoint, sin persistencia por usuario (`localStorage` solo se usa para tema claro/oscuro). Feature nueva de cero — no priorizada todavía.

### 2.3 Paginador de listado de clientes

✅ **Hecho.** `paginate(15)->withQueryString()` + `<el-pagination>` en `ClientesTable.vue`.

### 2.4 Lista de cuotas a pagar por rango de fechas (default: día actual)

❌ **No existe** pantalla dedicada con filtros de rango de fechas / cliente / estado (demoradas, próximas). El modelo `PrestamosDias` ya tiene las columnas necesarias y el Dashboard ya cuenta "cuotas vencidas" como KPI agregado, pero no hay listado navegable. No priorizada todavía.

### 2.5 Tabla de pagos en gestión

| Ítem | Estado | Evidencia |
|---|---|---|
| Detalle editable cuando el informe está en estado "remitir" | ❌ Pendiente | El detalle de un informe (`DetalleInformeModal.vue`) es solo lectura hoy |
| Progresar remisión con confirmación aprobar/pendiente | ❌ Pendiente | No existe esa transición. Nota: "Remitido" ya tiene `finish_estatus=1` en el seed, lo que hoy **impide** progresar desde ahí — revisar esa config si se retoma este ítem |

### 2.6 Reportes

| Ítem | Estado | Evidencia |
|---|---|---|
| Listado de préstamos agrupado por cliente/día/banco/tipo de pago/país/ciudad/grupo | ❌ No existe | No hay módulo "Reportes" separado del listado plano de `/prestamos` |
| Gráficas: torta por estado, utilidad, monto prestado/recibido/perdido | 🟡 Parcial | El Dashboard ya tiene gráficas reales con datos (`CarteraPorEstadoChart.vue`, `SerieMensualChart.vue`), pero son de barras (no torta) y no incluyen utilidad/monto perdido. Viven en el Dashboard, no en un módulo "Reportes" |

### 2.7 Tabla de transacciones (ingresos/egresos centralizados)

🟡 **Ya existe una tabla que cumple ese rol**: `customer_movement_histories` + `summary_customer_movements` (con `payment_report_id` nullable). **Decisión de arquitectura pendiente de confirmar**: si se quiere centralizar ingresos/egresos, se extiende esa tabla — `payment_reports` no es candidata porque está acoplada al dominio "informar un pago", no a movimientos genéricos.

### 2.8 Flujo de informe de pago

| Ítem | Estado | Evidencia |
|---|---|---|
| Bloquear selección de cuota ya vinculada a informe pendiente | ❌ Pendiente → **unificado en §1.3** | El backend ya calcula `pendientesPago2` (`PrestamosController::transformData`), pero `InformarPagoModal.vue` lo ignora al filtrar cuotas seleccionables |
| Leyenda "pendiente de confirmación" + n° comprobante | ❌ Pendiente → **unificado en §1.3** | Mismo dato de arriba, no se expone en ninguna vista |
| Pago de cuotas con saldo a favor | ✅ Hecho | Completo de punta a punta |
| Validar cliente seleccionado antes de habilitar "pagar" | ✅ Hecho | `InformarPagoModal.vue` (`puedeRegistrar`) |
| Botón general vs. botón por cliente | ✅ Hecho | `abrirInformar(clienteId?)` soporta ambos casos |
| Menú según permisos | ✅ Hecho | `Menu.php` filtra por `$user->cannot($item->permission)` — ver también §1.2 (CRUD de `modules`) |
| Reajuste de informe de pago | 🚫 Cerrado — ver §1.1 | |
| Email en cada cambio de estado | ❌ No existe | No hay ni un mailable para esto (ni siquiera al crear) |
| Aprobar → marcar cuotas pagadas + saldo a cartera + transaccional | 🟡 Parcial | Pasa por un endpoint genérico `change_estatus_report`, sin método `aprobar()` dedicado. Sí marca cuotas pagadas y sí registra en `customer_movement_histories` |
| Rechazar → motivo + soporte + liberar cuotas | 🟡 Parcial → **unificado en §1.3** | Motivo se exige; **soporte no** (`soporte=0` en el seed de estados para Rechazado); no hay nada que "liberar" porque hoy la cuota nunca queda reservada |
| KPI de informes pendientes clickeable con modal | ❌ Pendiente | El número ya está en el Dashboard (`kpis.informes_por_revisar`), sin `@click` ni modal |

---

## 3. Detalle de las features priorizadas

### 3.1 Retomar CRUD de `modules` (menú dinámico)

**Qué existe:** `app/Http/Controllers/ModulesController.php` + rutas en `routes/modules.php` + tabla `modules`,
completo del lado backend, sin uso.

**Qué falta:**
- Pantalla de frontend (`resources/js/pages/Modules.vue` o similar) — no existe hoy.
- Decidir la relación entre `modules` (tabla legada) y `menu_items` (tabla vigente que arma `Menu.php`) —
  hoy son dos conceptos separados y hay que definir si `modules` pasa a ser la fuente de `menu_items`,
  o si se gestiona `menu_items` directamente desde esta nueva pantalla.
- Conectar `Menu.php` a lo que se decida.

### 3.2 Validar en el backend el bloqueo de edición de informes rechazados/procesados — ✅ Hecho (2026-09-15)

`PaymentReportService::verifiedCurrentStatus()` ahora carga `payment_reports_movement_first.estatus_description`
y corta con una excepción si `finish_estatus` es true, antes de siquiera mirar el estado destino. Mismo mensaje
que ya usaba el frontend ("Este informe está en un estado final y no se puede modificar."). Regresión cubierta
en `PanelSmokeTest::test_no_se_puede_cambiar_estado_de_informe_finalizado`.

### 3.3 Estado de reserva de cuota (feature unificada)

**Objetivo del usuario:** que no se pueda informar el pago de una misma cuota más de una vez mientras esté
en algún préstamo activo con un informe en curso.

**Unifica estos 3 ítems sueltos del backlog original:**
1. Bloquear selección de cuota ya vinculada a un informe pendiente.
2. Mostrar leyenda "pendiente de confirmación" + n° de comprobante en la cuota.
3. Liberar la cuota si el informe que la reservaba es rechazado.

**Punto de partida:** el dato que indica si una cuota está vinculada a un informe no finalizado
(`pendientesPago2`, vía `PrestamosDias::pendientesPago()`) **ya existe en el backend** — el trabajo es:
- Exponerlo consistentemente en las vistas relevantes (`InformarPagoModal.vue`, tabla de cuotas).
- Usarlo para deshabilitar/ocultar la cuota en el selector de "Informar pago".
- Mostrar la leyenda + comprobante en la UI.
- Al rechazar un informe, verificar que las cuotas que tenía vinculadas queden libres para volver a
  informarse (ya no deberían aparecer como "reservadas" una vez rechazado el informe que las reservaba).

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
