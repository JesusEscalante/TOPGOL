# ⚽ TOP GOL — Sistema Integral de Gestión Deportiva y Reservas (V1)

Plataforma web completa desarrollada en **PHP 8.2+** bajo el patrón arquitectónico **Modelo-Vista-Controlador (MVC)** nativo, diseñada para la administración integral del complejo deportivo **TOP GOL**: alquiler y reserva de canchas sintéticas, gestión de eventos y espacios, control de cobros y caja diaria, punto de venta (POS) para bar/kiosco, kardex de inventario y analítica financiera en tiempo real.

---

## 📋 Tabla de Contenidos

- [Características Principales](#-características-principales)
- [Tecnologías y Arquitectura](#-tecnologías-y-arquitectura)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Requisitos del Sistema](#-requisitos-del-sistema)
- [Instalación y Configuración](#-instalación-y-configuración)
- [Migraciones de Base de Datos](#-migraciones-de-base-de-datos)
- [Credenciales de Prueba](#-credenciales-de-prueba)
- [Mapa de Rutas del Sistema](#-mapa-de-rutas-del-sistema)
- [Seguridad y Buenas Prácticas](#-seguridad-y-buenas-prácticas)
- [Licencia y Créditos](#-licencia-y-créditos)

---

## 🌟 Características Principales

### 1. ⚽ Gestión de Canchas y Espacios Deportivos
- El complejo deportivo TOP GOL cuenta con **seis (6) canchas sintéticas** de césped artificial para fútbol rápido / fútbol 5.
- Soporte en el sistema para administrar las 6 canchas de forma individual o agruparlas para eventos y torneos deportivos (sin asumir dimensiones reglamentarias oficiales de fútbol 7 u 11 no confirmadas por el negocio).
- Atributos operativos configurables: precio por hora, iluminación, techado, capacidad referencial y estado operativo (`disponible`, `mantenimiento`).
- Módulo de **Espacios para Eventos**: salones de reuniones y celebraciones con formulario de cotización y solicitud.

### 2. 📅 Motor de Reservas y Disponibilidad en Tiempo Real
- **Control estricto de disponibilidad**: Algoritmo matemático anti-traslapes que evalúa el intervalo completo (`cancha_id` + `fecha` + `hora_inicio` + `duracion`).
- **Flexibilidad horaria**: Soporte para duraciones de 30 minutos, 1 hora, 1.5 horas, etc., adaptado a los horarios operativos del negocio.
- **Control de asistencia y tolerancia**: Registro de puntualidad con tolerancia provisional de 10 minutos (`presente`, `tardanza`, `no_asistio`), con opción de liberación administrativa de cancha por inasistencia.
- **Reprogramación auditada**: Política provisional de reprogramación anticipada (mínimo 1 hora de anticipación) con historial de motivos y trazabilidad.

> [!NOTE]
> **Políticas y Horarios Provisionales (V1):** Los horarios de atención al público (Lunes a Viernes 09:00–23:00, Sábados y Domingos 07:00–23:00), la tarifa base (S/ 60.00/hora), la tolerancia de llegada (10 min) y las ventanas de anticipación (1 hora) son **reglas operativas provisionales de la Fase V1**, sujetas a confirmación y ajuste según los requerimientos definitivos de la administración de TOP GOL.

### 3. 💳 Control de Cobros, Pagos y Caja Diaria
- Arqueo de **Caja Diaria** en tiempo real con separación contable auditada:
  - Ingresos por alquiler de canchas y eventos.
  - Ingresos por ventas de productos de mostrador (bar / kiosco).
- Desglose por método de pago: **Efectivo**, **Niubiz POS** y **Yape**.
- Flujo de estados de pago: `pendiente`, `en_revision`, `verificado` y `rechazado`.
- Carga y verificación de comprobantes digitales de pago (JPG, PNG, PDF).
- Control de adelantos (mínimo S/ 20.00) y cobro ágil de saldos pendientes en caja.

### 4. 🛒 Punto de Venta (POS) y Kiosco / Bar
- Terminal POS optimizado para atención presencial rápida con teclado numérico y cálculo de vuelto.
- Catálogo de productos organizado por categorías (Hidratantes, Refrescos, Snacks, Accesorios).
- Generación de comprobantes y códigos de operación únicos.
- Previsión de idempotencia mediante tokens criptográficos para evitar doble cobro.

### 5. 📦 Inventario y Kardex Físico
- Control de stock actual y alertas configurables de stock mínimo / crítico.
- Registro automatizado de movimientos de inventario (`ingreso`, `salida`, `ajuste`) con registro de usuario responsable, motivo y saldos anterior/nuevo.
- Valorización financiera total del inventario a precio de venta al público (PVP).

### 6. 📊 Dashboard Administrativo y Reportes Financieros
- **Dashboard analítico**: KPIs de ingresos del día, comparación respecto al día anterior, reservas por estado, productos más vendidos y tasa de ocupación calculada contra capacidad operativa base.
- **Reportes avanzados**: Consolidación por rangos de fecha para ingresos, productos más vendidos, demanda por categorías y desglose de horas-cancha por franja horaria.
- **Exportación CSV**: Descarga de reportes estructurados para auditoría contable externa.
- **Aislamiento de Demostración (`es_demo`)**: Modo auditoría que permite separar completamente las ventas, pagos y reservas de calibración de la actividad comercial real.

---

## 🛠️ Tecnologías y Arquitectura

- **Lenguaje:** PHP 8.2+ (con directiva obligatoria `declare(strict_types=1);` en todos los archivos).
- **Arquitectura:** Modelo-Vista-Controlador (MVC) nativo sin dependencias pesadas de frameworks externos.
- **Base de Datos:** MySQL 5.7+ / MariaDB 10.4+ mediante conexión Singleton PDO y transacciones ACID.
- **Frontend:** HTML5 semántico, Bootstrap 5.3, Bootstrap Icons y JavaScript nativo (Vanilla ES6+).
- **Autenticación:** Sistema híbrido: autenticación nativa con `password_hash(PASSWORD_BCRYPT)` y **Google OAuth 2.0**.
- **Notificaciones:** Integración Telegram vía CallMeBot API.

---

## 📂 Estructura del Proyecto

```text
topgol/
├── app/
│   ├── Config/
│   │   └── Database.php             # Conexión Singleton PDO a MySQL
│   ├── Controllers/
│   │   ├── AuthController.php       # Login tradicional, Google OAuth y registro
│   │   ├── CanchaController.php     # Catálogo y administración de canchas
│   │   ├── CategoriaController.php  # CRUD de categorías de productos
│   │   ├── EspacioController.php    # Gestión de espacios para eventos
│   │   ├── HomeController.php       # Portada y catálogo de bienvenida
│   │   ├── NotificacionController.php # Centro de avisos del sistema
│   │   ├── PagoController.php       # Arqueo de caja diaria y control de saldos
│   │   ├── ProductoController.php   # Catálogo, inventario y kardex de productos
│   │   ├── ReporteController.php    # Reportes de ingresos y exportaciones CSV
│   │   ├── ReservaController.php    # Motor de reservas, reprogramación y asistencia
│   │   ├── UsuarioController.php    # Dashboard administrativo y usuarios
│   │   └── VentaController.php      # Terminal POS y ventas de kiosco
│   ├── Core/
│   │   ├── Controller.php           # Controlador base con renderizado y respuestas JSON
│   │   ├── Model.php                # Modelo base con operaciones preparadas PDO
│   │   └── Router.php               # Enrutador con soporte para parámetros dinámicos
│   ├── Helpers/
│   │   └── helpers.php              # Funciones auxiliares (CSRF, Auth, URLs, horarios)
│   ├── Models/
│   │   ├── Cancha.php               # Consultas y disponibilidad de canchas
│   │   ├── Categoria.php            # Modelo de categorías de bar/kiosco
│   │   ├── Espacio.php              # Modelo de espacios para eventos
│   │   ├── MovimientoInventario.php # Trazabilidad del kardex de inventario
│   │   ├── Notificacion.php         # Modelo de notificaciones a usuarios
│   │   ├── Pago.php                 # Modelo de pagos, comprobantes y caja diaria
│   │   ├── Producto.php             # Modelo de productos y valorización
│   │   ├── Reserva.php              # Modelo de reservas, asistencia y métricas
│   │   ├── Usuario.php              # Modelo de usuarios y credenciales
│   │   └── Venta.php                # Modelo de transacciones POS y reportes
│   ├── Services/
│   │   ├── GoogleOAuth.php          # Servicio de integración con Google Sign-In
│   │   └── TelegramNotifier.php     # Servicio de alertas vía Telegram / CallMeBot
│   └── Views/                       # Plantillas y vistas por módulo (PHP)
│       ├── auth/                    # Login, registro y callback OAuth
│       ├── canchas/                 # Catálogo, creación y edición de canchas
│       ├── categorias/              # Vistas de gestión de categorías
│       ├── contacto/                # Página de contacto e información
│       ├── espacios/                # Solicitud y cotización de eventos
│       ├── eventos/                 # Reserva de múltiples canchas para torneos
│       ├── home/                    # Portada institucional
│       ├── layout/                  # Plantillas de cabecera, navegación y pie
│       ├── pagos/                   # Panel de caja diaria y control de saldos
│       ├── productos/               # Catálogo de productos e inventario kardex
│       ├── reportes/                # Reportes financieros, ranking y exportaciones
│       ├── reservas/                # Formularios de reserva, mis reservas y admin
│       ├── usuarios/                # Dashboard administrativo y usuarios
│       └── ventas/                  # Terminal POS y arqueo de mostrador
├── config/
│   └── config.php                   # Constantes globales, URLs y cargador de .env
├── database/
│   ├── schema.sql                   # Estructura base de tablas y datos semilla
│   ├── migracion_espacios.sql       # Tablas de espacios para eventos
│   └── migrations/                  # Migraciones evolutivas numeradas (002 a 015)
│       ├── 002_add_google_oauth_fields.sql
│       ├── 003_add_pago_comprobante_reservas.sql
│       ├── 004_create_productos.sql
│       ├── 005_admin_presencial_reservas.sql
│       ├── 006_add_evento_grupo.sql
│       ├── 007_create_notificaciones.sql
│       ├── 008_add_contacto_telefono.sql
│       ├── 009_duracion_media_hora.sql
│       ├── 010_add_origen_reservas.sql
│       ├── 011_create_pagos_table.sql
│       ├── 012_protect_pagos_fk_restrict.sql
│       ├── 013_control_asistencia_reprogramacion.sql
│       ├── 014_ventas_productos_inventario.sql
│       └── 015_add_es_demo_flag.sql
├── public/                          # Punto de entrada público del servidor web
│   ├── .htaccess                    # Reescritura al Front Controller (index.php)
│   ├── index.php                    # Front Controller y registro de rutas
│   ├── assets/                      # Hojas de estilo CSS e interactividad JS
│   └── uploads/                     # Directorio de comprobantes (protegido)
├── tests/
│   └── run_closing_tests.php        # Suite de pruebas automatizadas de consistencia
├── .env.example                     # Plantilla de variables de entorno para despliegue
├── .gitignore                       # Reglas de exclusión de Git (ignora .env y volcados)
├── .htaccess                        # Redirección raíz hacia public/
└── README.md                        # Documentación técnica del proyecto
```

---

## 💻 Requisitos del Sistema

- **PHP 8.1** o superior (recomendado **PHP 8.2+**).
- Extensiones PHP requeridas:
  - `pdo` y `pdo_mysql` (acceso a base de datos).
  - `mbstring` (manipulación segura de caracteres UTF-8).
  - `curl` (comunicación con APIs de Google y Telegram).
  - `session` (manejo de sesiones de usuario).
  - `fileinfo` (validación segura de archivos subidos).
- **MySQL 5.7+** o **MariaDB 10.4+**.
- Servidor web **Apache** con el módulo `mod_rewrite` habilitado (incluido en XAMPP, WampServer y LAMP).

---

## 🚀 Instalación y Configuración

### 1. Clonar o Ubicar el Proyecto
Ubica el repositorio en la carpeta raíz de tu servidor web local (por ejemplo en XAMPP para Windows):
```bash
cd C:\xampp\htdocs
git clone https://github.com/JesusEscalanteAlanoca/TOPGOL.git topgol
```

### 2. Configurar las Variables de Entorno
Copia el archivo de plantilla `.env.example` para generar tu archivo local `.env`:
```bash
cp .env.example .env
```
Edita `.env` con tus parámetros de conexión locales:
```ini
APP_NAME="TOP GOL"
APP_ENV=development
APP_URL=http://localhost/topgol

# Base de Datos MySQL
DB_HOST=localhost
DB_PORT=3306
DB_NAME=topgol_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# Zona Horaria
APP_TIMEZONE=America/Lima

# Google OAuth 2.0 (Opcional - Configurar en Google Cloud Console)
GOOGLE_CLIENT_ID=tu_cliente_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu_cliente_secreto
GOOGLE_REDIRECT_URI=http://localhost/topgol/auth/google/callback

# Notificaciones Telegram vía CallMeBot (Opcional)
TELEGRAM_ENABLED=false
TELEGRAM_USER=@tu_usuario_telegram
TELEGRAM_DEBUG=false
```

### 3. Configurar la Base de Datos y Migraciones
1. Crea la base de datos `topgol_db` en tu servidor MySQL:
   ```sql
   CREATE DATABASE topgol_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Importa el esquema base:
   ```bash
   mysql -u root -p topgol_db < database/schema.sql
   ```
3. Ejecuta las migraciones evolutivas en orden (ver sección siguiente):
   ```bash
   mysql -u root -p topgol_db < database/migracion_espacios.sql
   mysql -u root -p topgol_db < database/migrations/010_add_origen_reservas.sql
   mysql -u root -p topgol_db < database/migrations/011_create_pagos_table.sql
   mysql -u root -p topgol_db < database/migrations/012_protect_pagos_fk_restrict.sql
   mysql -u root -p topgol_db < database/migrations/013_control_asistencia_reprogramacion.sql
   mysql -u root -p topgol_db < database/migrations/014_ventas_productos_inventario.sql
   mysql -u root -p topgol_db < database/migrations/015_add_es_demo_flag.sql
   ```

### 4. Ejecutar las Pruebas de Consistencia
Para verificar que el entorno local esté 100% operativo y sin discrepancias contables, ejecuta la suite de pruebas:
```bash
php tests/run_closing_tests.php
```

### 5. Abrir en el Navegador
Asegúrate de que Apache y MySQL estén iniciados. Luego accede a:
```text
http://localhost/topgol/
```

---

## 🗄️ Migraciones de Base de Datos

Las modificaciones estructurales se encuentran versionadas cronológicamente en [`database/migrations/`](file:///c:/Users/MSI/Documents/TOPGOL/database/migrations/):

| Archivo | Propósito / Cambios |
| :--- | :--- |
| `002_add_google_oauth_fields.sql` | Agrega `google_id` y `avatar` a la tabla `usuarios`. |
| `003_add_pago_comprobante_reservas.sql` | Campos para comprobantes de pago digitales y estado de verificación en `reservas`. |
| `004_create_productos.sql` | Estructura inicial del catálogo de productos y precios. |
| `005_admin_presencial_reservas.sql` | Habilita cobros presenciales en Efectivo y Niubiz POS para el administrador. |
| `006_add_evento_grupo.sql` | Agrupa múltiples canchas reservadas bajo un mismo identificador de evento o torneo. |
| `007_create_notificaciones.sql` | Tabla para alertas del sistema a clientes y administradores. |
| `008_add_contacto_telefono.sql` | Campo obligatorio de teléfono celular / WhatsApp para contacto de reservas. |
| `009_duracion_media_hora.sql` | Soporte decimal (`DECIMAL(4,2)`) para bloques horarios fraccionados (ej. 1.5 horas). |
| `010_add_origen_reservas.sql` | Canal de reserva (`web`, `presencial`, `llamada`, `whatsapp`) para analítica de ventas. |
| `011_create_pagos_table.sql` | Tabla `pagos` para trazabilidad de adelantos, saldos y arqueo de caja. |
| `012_protect_pagos_fk_restrict.sql` | Integridad referencial con `ON DELETE RESTRICT` para proteger pagos auditados. |
| `013_control_asistencia_reprogramacion.sql` | Campos de asistencia, puntualidad, tolerancia y políticas de reprogramación. |
| `014_ventas_productos_inventario.sql` | Tablas de ventas POS, detalle de ventas, categorías y movimientos de kardex. |
| `015_add_es_demo_flag.sql` | Indicador `es_demo` indexado para aislar datos de simulación en `ventas`, `pagos` y `reservas`. |
| `../migracion_espacios.sql` | Tablas `espacios_eventos` y `solicitudes_espacios` para salones multiuso. |

---

## 🔑 Cuentas de Acceso para Pruebas Locales

Las siguientes credenciales corresponden exclusivamente a **datos semilla simulados para pruebas locales en entorno de desarrollo**:

| Rol | Correo de Prueba | Contraseña de Prueba | Permisos en Desarrollo |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@topgol.com` | `admin123` | Control total: dashboard, caja diaria, POS, inventario, reportes, canchas y saldos. |
| **Cliente** | `cliente@topgol.com` | `cliente123` | Portal público, reserva de canchas, consulta de disponibilidad y mis reservas. |

> [!WARNING]
> En entornos de producción reales, los usuarios deben registrarse individualmente con contraseñas seguras y únicas. **El repositorio no contiene datos privados, números de teléfono ni contraseñas reales de clientes.**

---

## 🛣️ Mapa de Rutas del Sistema

### Rutas Públicas y de Clientes
| Método | Ruta | Controlador y Acción | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | `HomeController@index` | Portada institucional y horarios próximos |
| `GET` | `/canchas` | `CanchaController@index` | Catálogo público con selector compacto de horarios |
| `GET` | `/cancha/ver/{id}` | `CanchaController@show` | Ficha técnica y tarifas de una cancha |
| `GET` | `/espacios` | `EspacioController@index` | Catálogo de salones y cotización de eventos |
| `POST` | `/espacios/solicitar` | `EspacioController@solicitar` | Envío de solicitud de alquiler de espacio |
| `GET` | `/contacto` | `HomeController@contacto` | Formulario de contacto y ubicación del complejo |
| `GET` | `/login` | `AuthController@showLogin` | Pantalla de inicio de sesión |
| `POST` | `/login` | `AuthController@login` | Procesamiento de credenciales |
| `GET` | `/auth/google` | `AuthController@googleRedirect` | Redirección hacia Google Sign-In |
| `GET` | `/auth/google/callback`| `AuthController@googleCallback` | Retorno y validación del token de Google |
| `GET` | `/register` | `AuthController@showRegister` | Registro de nuevos clientes |
| `POST` | `/register` | `AuthController@register` | Procesamiento del registro de cliente |
| `GET` | `/logout` | `AuthController@logout` | Cierre seguro de sesión |
| `GET` | `/reserva/crear` | `ReservaController@create` | Formulario de reserva con selector de bloques |
| `POST` | `/reserva/guardar` | `ReservaController@store` | Confirmación y guardado de reserva |
| `GET` | `/mis-reservas` | `ReservaController@misReservas` | Historial de reservas del cliente |
| `GET` | `/reserva/detalle/{id}`| `ReservaController@detalle` | Comprobante y estado detallado de una reserva |
| `POST` | `/reserva/cancelar/{id}`| `ReservaController@cancel` | Cancelación autónoma según políticas |

### Rutas del Panel Administrativo (Solo Administrador)
| Método | Ruta | Controlador y Acción | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/dashboard` | `UsuarioController@dashboard` | Métricas operativas, ocupación y ventas de hoy |
| `GET` | `/admin/caja` | `PagoController@caja` | Arqueo de caja diaria y desglose por método |
| `GET` | `/admin/pagos` | `PagoController@index` | Control de saldos y verificación de comprobantes |
| `POST` | `/admin/pagos/cobrar` | `PagoController@registrarCobro` | Cobro presencial de saldos en efectivo o POS |
| `POST` | `/admin/pagos/verificar/{id}` | `PagoController@verificar` | Validación manual de comprobante Yape |
| `POST` | `/admin/pagos/rechazar/{id}` | `PagoController@rechazar` | Rechazo de comprobante y liberación de horario |
| `GET` | `/admin/ventas/pos` | `VentaController@pos` | Terminal POS táctil de Bar / Kiosco |
| `POST` | `/admin/ventas/procesar` | `VentaController@procesar` | Registro de venta directa y descuento de stock |
| `GET` | `/admin/ventas/historial` | `VentaController@historial` | Historial y arqueo de comprobantes POS |
| `GET` | `/admin/inventario` | `ProductoController@inventario` | Kardex de entradas, salidas y valorización |
| `POST` | `/admin/inventario/ajuste`| `ProductoController@registrarAjuste` | Ajuste manual de stock con motivo auditado |
| `GET` | `/admin/productos` | `ProductoController@index` | Mantenimiento del catálogo de productos |
| `POST` | `/admin/productos/guardar`| `ProductoController@store` | Alta de nuevo producto |
| `POST` | `/admin/productos/actualizar/{id}` | `ProductoController@update` | Edición de producto y precios |
| `GET` | `/admin/reportes` | `ReporteController@index` | Reporte consolidado de ingresos y demanda |
| `GET` | `/admin/reportes/exportar`| `ReporteController@exportarCsv` | Exportación de reporte a archivo CSV |
| `POST` | `/admin/reservas/asistencia/{id}` | `ReservaController@registrarAsistencia` | Check-in de puntualidad y tolerancia |
| `POST` | `/admin/reservas/reprogramar/{id}`| `ReservaController@reprogramar` | Reprogramación de cancha, fecha u hora |

---

## 🛡️ Seguridad y Buenas Prácticas

1. **Protección Anti-CSRF:** Generación y validación estricta de tokens criptográficos en todas las peticiones de mutación de estado (`POST`).
2. **Mitigación de Inyección SQL (SQLi):** Consultas preparadas parametrizadas en el 100% de las operaciones contra la base de datos a través de PDO.
3. **Hashing de Contraseñas:** Algoritmo nativo `PASSWORD_BCRYPT` con costo computacional adecuado.
4. **Seguridad de Sesiones:** Flags de cookies `HttpOnly`, `SameSite=Lax` y regeneración periódica de ID (`session_regenerate_id(true)`) contra fijación y secuestro.
5. **Mitigación de XSS:** Escapado defensivo obligatorio con `htmlspecialchars` en la capa de vistas.
6. **Protección de Datos Sensibles:** Reglas estrictas en `.gitignore` para impedir que archivos `.env`, credenciales de API o volcados de base de datos se publiquen en repositorios públicos.
7. **Tipado Estricto:** Cada archivo PHP cuenta con `declare(strict_types=1);` para garantizar solidez operativa y evitar coerción implícita de tipos.

---

## 📄 Licencia y Créditos

Proyecto desarrollado para el complejo deportivo **TOP GOL**.
Todos los derechos reservados © 2026.