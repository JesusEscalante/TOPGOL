# TOP GOL — Plan por fases para Antigravity

**Fecha:** 27 de septiembre de 2026
**Proyecto de trabajo:** `C:\Users\MSI\Documents\TOPGOL`
**Base de decisiones:** `TOP_GOL_CONTEXTO_MAESTRO_V2.md` y revisión del proyecto PHP/MVC proporcionado. Las reglas no confirmadas por el encargado son **propuestas V1**, no hechos de la empresa.

## Cómo usar este documento

Pega **solo el prompt de la fase que corresponde**, empezando por la fase 1. Al terminar cada fase, comprueba el resultado en `http://localhost:8080`, comparte el reporte y los problemas observados, y después continúa. Antigravity debe trabajar siempre en el clon de Documentos y conservar los cambios de fases anteriores. Un HTTP 200 demuestra que una página carga; **no** demuestra que el precio, la disponibilidad o una confirmación sean correctos.

### Estado inicial ya informado

- El clon de Documentos contiene seis archivos modificados del portal; `git diff --check` quedó limpio. Seis canchas a S/60 están en la base local; permanecen cinco reservas anteriores con sus importes históricos.
- El servidor del clon se inició en el puerto 8080. Se comprobó que Inicio, Canchas, Eventos y Contacto responden 200. Las pruebas funcionales de reserva siguen pendientes.
- El Google OAuth es trabajo del compañero. No modificar `AuthController`, `GoogleOAuth`, claves ni `.env`.
- El repositorio de origen ya incluía un `.env` y comprobantes rastreados. Copiar solo seis archivos **no elimina** esos elementos ni su historial. **No hacer push** hasta revisar ese problema con el compañero y sustituir las credenciales expuestas si corresponden. No pegar secretos ni comprobantes en informes.
- La página de pago del proyecto recibido tiene un QR generado con texto de ejemplo, un número/titular de ejemplo y BCP. **No pagar a esos datos**, ni presentar el QR como real.

## Decisión de diseño para la hora

Hay dos conceptos distintos: **hora de inicio** (por ejemplo, `13:30`) y **duración** (por ejemplo, `1 hora y 30 minutos`). Ambos deben admitir bloques de 30 minutos. El selector compacto recomendado es: fecha; campo **hora** (`07` a `22`, según el día); campo **minutos** (`00` o `30`); y un campo separado **duración** (`30 minutos`, `1 hora`, `1 hora y 30 minutos`, etc.). Se muestra al lado la salida y el precio antes de continuar: `13:30–15:00 · S/90`. La hora elegida se transmite al backend como `HH:MM` para conservar la compatibilidad con las rutas y la base existentes.

Este diseño evita una lista de más de 30 horas de inicio y permite consultar `11:30`, `12:30`, `13:30`, etc. **La disponibilidad debe calcularse para el intervalo completo elegido**: cancha + fecha + inicio + duración. Si una reserva ocupa `11:00–11:30`, otra puede comenzar a las `11:30`; una reserva `11:30–12:30` debe impedir intervalos que se le solapen.

El horario base V1 es **provisional**: lunes a viernes `09:00–23:00`, sábados y domingos `07:00–23:00`, con zona `America/Lima`. Por ejemplo, `22:30–23:00` cabe; `22:30–23:30` no cabe. No mostrar `23:00` o `23:30` como inicios permitidos si se cierra a las 23:00. Más adelante se podrá permitir al administrador una excepción documentada, sin abrir el horario público de forma implícita.

### Hallazgos concretos que sustentan la fase 1

En el ZIP/GitHub revisado, `app/Views/home/index.php` y `app/Views/canchas/index.php` muestran cinco bloques próximos **solo en horas exactas**; el buscador de Inicio filtra también por horas exactas. `app/Views/reservas/formulario.php` y la vista administrativa de `app/Views/reservas/create.php` sí generan opciones de `:30`, pero en listas extensas, incluso hasta `23:30`; su lógica de «hoy» salta a la hora siguiente. `app/Models/Reserva.php::obtenerSlotsOcupados()` marca una hora completa como ocupada cuando se solapa con una reserva de solo media hora. Por ello, la etiqueta «Horarios disponibles» en las tarjetas no demuestra disponibilidad exacta para el intervalo que busca el cliente. Revisar el clon actual antes de editar, pues las seis modificaciones recientes cambiaron algunas vistas.

---

## Fase 1 — Horas, media hora y disponibilidad comprensible **[hacer ahora]**

**Resultado esperado:** cualquier persona puede buscar y elegir `HH:00` o `HH:30` sin una lista larga; ve el precio de la duración escogida; ni el navegador ni el backend aceptan horarios fuera de apertura, en el pasado o solapados. La pantalla no llama «disponibles» a horas no verificadas.

### Prompt listo para pegar en Antigravity

> Trabaja únicamente en `C:\Users\MSI\Documents\TOPGOL` y conserva los seis cambios previos. Implementa **solo la fase 1: selección compacta de hora y disponibilidad**. Antes de editar, rastrea cómo pasan fecha, `horario`, cancha y duración por Inicio, Canchas, formulario del cliente, formulario del administrador, controlador y modelos. Aplica las decisiones del archivo `PLAN_TOPGOL_FASES_ANTIGRAVITY.md` que te compartiré, sin crear una segunda lógica contradictoria.
>
> 1. Sustituye la lista larga de horas por dos controles compactos: **hora** y **minutos 00/30**, manteniendo **duración** separada. Permite inicios como 11:30 y duraciones de 30, 60, 90 y 120 minutos, así como otras duraciones de 30 en 30 admitidas por el sistema. Combina la selección como `HH:MM` para no romper los enlaces ni el backend. Conserva selección al navegar de Inicio → Canchas → Reserva → Resumen. En pantalla pequeña debe seguir siendo claro.
> 2. Ajusta el buscador y el filtro de Canchas para que la disponibilidad se compruebe por **cancha, fecha y todo el intervalo elegido**; si falta una hora o duración, no afirmes que la cancha está libre para una reserva concreta. Retira o rediseña las cinco tarjetas de horas próximas que hoy aparecen como «Horarios disponibles» y solo muestran horas enteras: muestra un acceso compacto para consultar fecha/hora/duración, o ejemplos calculados de verdad para ese intervalo. No alargues la tarjeta con 30 botones.
> 3. En cliente y administrador aplica horario base provisional `09:00–23:00` lunes a viernes, `07:00–23:00` fines de semana, zona `America/Lima`. Nunca permitas fin posterior a las 23:00 ni inicios en el pasado para el día actual. Verifica también estas reglas **del lado del servidor**: no confíes solo en HTML/JavaScript ni en el valor enviado por la URL. En backend conserva la comprobación de solapamiento por intervalo completo y evita que la vista marque ocupada la hora entera cuando solo se reservó media hora.
> 4. Muestra antes de continuar inicio, fin, duración y precio correctos (S/60 por hora; S/30 por media hora). En el formulario de reserva retira las características no comprobadas que todavía aparecen como hechos (por ejemplo iluminación, césped, camerinos o capacidad) sin cambiar datos internos de las canchas. No alteres pago, estados de reserva, OAuth, `.env`, comprobantes existentes ni las cinco reservas de la base local.
> 5. Comprueba desde el clon: `11:30 + 30 min = 12:00 / S/30`, `13:30 + 90 min = 15:00 / S/90`, una reserva `11:00–11:30` deja libre `11:30` si no hay otro choque; `22:30 + 30 min` se permite y `22:30 + 60 min` se rechaza; apertura de un lunes a las 09:00 y de un sábado a las 07:00; hoy en una hora pasada se rechaza. Usa datos de prueba aislados cuando necesites insertar una reserva; no edites las cinco existentes. Si no puedes probar algún caso, dilo expresamente.
> 6. Entrega lista exacta de archivos editados, evidencia del comportamiento y resultado de `git status --short` y `git diff --check`. No hagas `git add`, commit ni push. Detente al terminar esta fase.

**Qué revisar personalmente:** busca `11:30` en la fecha elegida, reserva 30 minutos y verifica el resumen; repite con 1 hora y 30 minutos; comprueba que no aparecen 23:30 como inicio ni cinco «horarios disponibles» sin duración asociada. No subas ningún comprobante real todavía.

---

## Fase 2 — Estado de reserva, verificación y bloqueo de pagos ficticios

**Dependencia:** fase 1 validada. **Meta:** el comprobante no confirma por sí solo. En el código base `ReservaController::store()` da `estado = confirmada` tanto a cliente como a administrador; `updatePayment()` solo cambia `pago_estado`. Cambiar ambos estados con reglas claras y de forma consistente: cliente `pendiente` + pago `en_revision`; administrador verifica → `confirmada` + `verificado`; rechaza → `cancelada` + `rechazado`, liberando horario. No contar una imagen subida como ingreso cobrado. Evitar verificaciones duplicadas y estados contradictorios. Probar con datos aislados, incluidas dos solicitudes para el mismo intervalo y rechazo.

Quitar el QR, número y titular **de ejemplo**; retirar BCP de la experiencia pública y de la aceptación del servidor, sin borrar datos históricos. Mientras falte QR auténtico, impedir un pago público a datos falsos y mostrar claramente «Yape pendiente de configuración». Las reservas manuales de prueba pueden probarse con cuentas locales y pagos simulados **solo en entorno de pruebas**. No tocar OAuth. No enviar avisos externos de prueba (por ejemplo Telegram) si existe una integración activa: verificar configuración antes.

**Entrega por fase:** rutas y archivos editados, estados antes/después en una base de prueba, controles de solapamiento, `git status --short`, `git diff --check`; sin commit ni push.

## Fase 3 — Yape real y reserva temporal

**Dependencia:** QR auténtico y titular/número autorizados por Top Gol para exhibición. Mostrar la **imagen real del QR**, sin generar un QR a partir de texto ni llamar una API de pagos. Adelanto web S/20 **por cancha**, comprobante y validación manual por encargado; saldo final visible. Probar monto con una y varias canchas cuando exista soporte real de varias canchas. Mientras falte el QR, **seguir con otras fases** y dejar esta fase pendiente; no inventar un reemplazo.

Implementar después, de manera separable, la propuesta V1 de retención temporal de 10 minutos durante el pago; caducidad y liberación seguras, y protección frente a dos usuarios intentando reservar a la vez. No iniciar un contador visual sin un bloqueo real en el servidor. Documentar reglas de expiración y errores recuperables. Verificar todo en datos de prueba.

## Fase 4 — Operación diaria del administrador

Registrar reservas presenciales/telefónicas vinculadas al cliente real, no al usuario administrador; poder consultar calendario, saldo y pago final real. Corregir enlaces/rutas inexistentes si siguen presentes. Permitir registrar llegada tarde, ausencia y reprogramación con trazabilidad según **propuestas V1**: tolerancia 10 minutos; tardanza no amplía hora de término; reprogramar avisando 1 hora antes y solo si hay disponibilidad; ausencia puede perder adelanto. No ejecutar automáticamente pérdidas de dinero ni confirmaciones de asistencia sin una decisión consciente del encargado. Probar permisos de cliente/admin y solapamientos.

## Fase 5 — Eventos, varias canchas y espacio de cumpleaños

Revisar eventos ya existentes antes de crear tablas o flujos nuevos. Soportar selección real de varias de las seis canchas, precio y adelanto proporcionales salvo precio especial autorizado por el administrador, y comprobación atómica de todos los intervalos. Diseñar «Cumpleaños / uso del espacio» como **propuesta provisional** (mesas y pantalla por confirmar), sin ofrecer como servicio definitivamente contratado lo que aún no confirmó Top Gol. No reutilizar una cancha como si fuera el espacio independiente sin validar el modelo. Probar anulación y disponibilidad de todas las canchas incluidas.

## Fase 6 — Ventas e inventario sin números inventados

Rubros/categorías administrables, productos, movimientos de stock, ventas y detalle de venta con totales reales. Registrar una venta y comprobar que descuenta stock una sola vez; reversión controlada si se cancela. Reemplazar las cifras fijas del panel y separar importes esperados de dinero efectivamente cobrado. Mantener datos demostrativos claramente marcados y no mezclarlos con ingresos reales.

## Fase 7 — Revisión final y GitHub

Regresión completa: cliente normal sin Google, una cancha, media hora, pago pendiente/verificado/rechazado, varias canchas, administrador, evento, venta, stock, rutas, móvil y controles de acceso. Revisar diferencias exactas en el clon, respaldos y migraciones **sin importar de nuevo el esquema sobre la base con datos**. Coordinar con el compañero el `.env`, OAuth y comprobantes ya publicados: ignorarlos en adelante no borra su historial; evaluar retirada segura, sustitución de secretos y resguardo de archivos sin modificar la integración a ciegas. Solo tras revisión compartida preparar commit/push en el repositorio correcto.

## Informe mínimo que Antigravity debe entregar al terminar cada fase

1. Qué objetivo terminó y cuáles quedaron pendientes.
2. Archivos y tablas cambiados; cómo preservó datos existentes.
3. Pruebas realizadas con entradas y resultados verificables; qué no probó.
4. Riesgos observados y requisitos provisionales aún pendientes de Top Gol.
5. `git status --short` y `git diff --check`; sin enseñar secretos ni fotografías de comprobantes.
