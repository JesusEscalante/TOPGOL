# ⚽ TOP GOL - Sistema Web de Alquiler y Reserva de Canchas de Fútbol

Sistema web completo desarrollado en **PHP 8+** bajo la arquitectura **Modelo-Vista-Controlador (MVC)**, diseñado específicamente para la gestión, reserva y administración de canchas deportivas de la empresa **TOP GOL**.

---

## 📋 Tabla de Contenidos
- [Características Principales](#-características-principales)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Requisitos del Sistema](#-requisitos-del-sistema)
- [Instalación Paso a Paso](#-instalación-paso-a-paso)
- [Credenciales de Prueba](#-credenciales-de-prueba)
- [Mapa de Rutas](#-mapa-de-rutas)
- [Seguridad Implementada](#-seguridad-implementada)
- [Créditos](#-créditos)

---

## 🌟 Características Principales

### ⚽ Gestión de Canchas (CRUD Completo - Solo Administradores)
- Registro, edición, visualización y eliminación de canchas deportivas.
- Soporte para formatos: **Fútbol 5**, **Fútbol 7** y **Fútbol 11**.
- Atributos detallados: capacidad de jugadores, precio por hora, iluminación LED, techado impermeable y estado operativo (`disponible`, `mantenimiento`).

### 📅 Sistema Inteligente de Reservas
- Selección interactiva de cancha, fecha y horario.
- **Control de disponibilidad en tiempo real**: Prevención estricta de traslapes y reservas cruzadas en el mismo horario y fecha.
- **Cálculo dinámico del total**: Total a pagar calculado al instante según duración en horas y tarifa de la cancha elegida.
- Estados de reserva: `pendiente`, `confirmada`, `cancelada` y `finalizada`.
- Módulo **Mis Reservas** para clientes con historial y opción de cancelación autónoma.
- Panel administrativo de reservas para cambiar estados rápidamente.

### 📊 Dashboard Administrativo
- Métricas en tiempo real: Total de ingresos recaudados, total de reservas, reservas confirmadas, pendientes, canchas y clientes registrados.
- Tabla rápida de últimas reservas registradas.

### 🔐 Autenticación y Roles
- Login y Registro con validación robusta en servidor y cliente.
- Roles diferenciados: **Administrador** y **Cliente**.
- Protección contra secuestro de sesiones y fijación de sesiones (`session_regenerate_id`).

### 🎨 Diseño y Frontend
- Construido con **Bootstrap 5** y **Bootstrap Icons** (CDN).
- Paleta de colores corporativa: Verde césped (`#1a7a3a`), Dorado trofeo (`#f5c842`), Negro carbón (`#111827`) y blanco.
- Totalmente **responsive** adaptable a teléfonos, tablets y computadoras.
- Mensajes Flash integrados para confirmaciones y alertas.

---

## 📂 Estructura del Proyecto

```text
topgol/
├── app/
│   ├── Config/
│   │   └── Database.php          # Conexión Singleton PDO a MySQL
│   ├── Controllers/
│   │   ├── AuthController.php    # Autenticación (Login, Registro, Logout)
│   │   ├── CanchaController.php  # CRUD de canchas
│   │   ├── HomeController.php    # Página principal y portada
│   │   ├── ReservaController.php # Lógica de reservas y disponibilidad
│   │   └── UsuarioController.php # Dashboard admin y lista de usuarios
│   ├── Core/
│   │   ├── Controller.php        # Clase base para controladores
│   │   ├── Model.php             # Clase base con abstracción PDO
│   │   └── Router.php            # Despachador de rutas con parámetros dinámicos
│   ├── Helpers/
│   │   └── helpers.php           # Funciones de apoyo (auth, flash, csrf, urls)
│   ├── Models/
│   │   ├── Cancha.php            # Modelo y consultas de canchas
│   │   ├── Reserva.php           # Modelo de reservas y validación de horarios
│   │   └── Usuario.php           # Modelo de usuarios y credenciales
│   └── Views/
│       ├── auth/                 # Vistas de login y registro
│       ├── canchas/              # Vistas de catálogo, creación, edición y detalle
│       ├── home/                 # Vista de portada
│       ├── layout/               # Header y Footer con navegación y estilos
│       ├── reservas/             # Vistas de reserva, mis reservas y gestión admin
│       └── usuarios/             # Dashboard y listado de usuarios
├── config/
│   └── config.php                # Constantes globales, URLs y parser de .env
├── database/
│   └── schema.sql                # Script de creación de tablas y datos semilla
├── public/                       # Raíz pública del servidor web
│   ├── .htaccess                 # Reescritura a index.php
│   ├── index.php                 # Front Controller y registro de rutas
│   └── assets/
│       ├── css/style.css         # Estilos corporativos personalizados
│       └── js/main.js            # Lógica dinámica en JavaScript
├── .env                          # Configuración local de base de datos y URL
├── .env.example                  # Plantilla de variables de entorno
├── .htaccess                     # Redirección automática de la raíz a public/
└── README.md                     # Documentación oficial del proyecto
```

---

## 💻 Requisitos del Sistema

- **PHP 8.0** o superior (Probado en PHP 8.2+).
- Extensiones PHP: `pdo`, `pdo_mysql`, `session`, `mbstring`.
- **MySQL 5.7+** o **MariaDB 10.4+**.
- Servidor web **Apache** con el módulo `mod_rewrite` activado (incluido por defecto en XAMPP).

---

## 🚀 Instalación Paso a Paso

### 1. Ubicación del Proyecto
Ubica la carpeta del proyecto dentro del directorio raíz de tu servidor web (por ejemplo, en XAMPP para Windows):
```text
C:\xampp\htdocs\topgol
```

### 2. Configurar el archivo `.env`
El archivo `.env` ya viene preconfigurado para un entorno XAMPP típico. Si tu configuración de MySQL usa otra contraseña o puerto, modifícalo:
```ini
APP_NAME="TOP GOL"
APP_ENV=development
APP_URL=http://localhost/topgol

DB_HOST=localhost
DB_PORT=3306
DB_NAME=topgol_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

APP_TIMEZONE=America/Bogota
```

### 3. Importar la Base de Datos
Tienes dos opciones para crear la base de datos `topgol_db` y cargar los datos de prueba:

#### Opción A: Mediante phpMyAdmin
1. Abre tu navegador y dirígete a `http://localhost/phpmyadmin`.
2. Haz clic en la pestaña **Importar**.
3. Selecciona el archivo ubicado en `database/schema.sql`.
4. Presiona el botón **Importar / Continuar**.

#### Opción B: Mediante Consola de Comandos
```bash
mysql -u root -p < database/schema.sql
```

### 4. Abrir en el Navegador
Asegúrate de que los módulos **Apache** y **MySQL** estén iniciados en el panel de XAMPP. Luego, ingresa en tu navegador a:
```text
http://localhost/topgol/
```
*(Gracias a la configuración de `.htaccess`, todas las peticiones se redirigen automáticamente y con seguridad a la carpeta `public/`).*

---

## 🔑 Credenciales de Prueba

El script de base de datos incluye cuentas precreadas para probar los diferentes roles:

| Rol | Correo Electrónico | Contraseña | Permisos |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@topgol.com` | `admin123` | Control total, CRUD de canchas, cambio de estados y métricas |
| **Cliente** | `cliente@topgol.com` | `cliente123` | Explorar canchas, realizar reservas y cancelar reservas propias |

---

## 🛣️ Mapa de Rutas

| Método | Ruta | Controlador y Acción | Acceso / Middleware |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | `HomeController@index` | Público |
| `GET` | `/canchas` | `CanchaController@index` | Público |
| `GET` | `/cancha/ver/{id}` | `CanchaController@show` | Público |
| `GET` | `/cancha/crear` | `CanchaController@create` | Solo Administrador |
| `POST` | `/cancha/guardar` | `CanchaController@store` | Solo Administrador |
| `GET` | `/cancha/editar/{id}` | `CanchaController@edit` | Solo Administrador |
| `POST` | `/cancha/actualizar/{id}`| `CanchaController@update` | Solo Administrador |
| `GET` | `/cancha/eliminar/{id}` | `CanchaController@delete` | Solo Administrador |
| `GET` | `/reservas` | `ReservaController@index` | Solo Administrador |
| `GET` | `/reserva/crear` | `ReservaController@create` | Usuarios Autenticados |
| `GET` | `/reserva/crear/{id}` | `ReservaController@create` | Usuarios Autenticados |
| `POST` | `/reserva/guardar` | `ReservaController@store` | Usuarios Autenticados |
| `GET` | `/reserva/cancelar/{id}`| `ReservaController@cancel` | Usuarios Autenticados |
| `POST` | `/reserva/estado/{id}` | `ReservaController@updateStatus`| Solo Administrador |
| `GET` | `/mis-reservas` | `ReservaController@misReservas` | Usuarios Autenticados |
| `GET` | `/admin/dashboard` | `UsuarioController@dashboard` | Solo Administrador |
| `GET` | `/admin/usuarios` | `UsuarioController@index` | Solo Administrador |
| `GET` | `/api/cancha/disponibilidad`| `ReservaController@checkAvailability`| Público (AJAX) |
| `GET` | `/login` | `AuthController@showLogin` | Solo Invitados |
| `POST` | `/login` | `AuthController@login` | Solo Invitados |
| `GET` | `/register` | `AuthController@showRegister` | Solo Invitados |
| `POST` | `/register` | `AuthController@register` | Solo Invitados |
| `GET` | `/logout` | `AuthController@logout` | Usuarios Autenticados |

---

## 🛡️ Seguridad Implementada

1. **Protección CSRF (Cross-Site Request Forgery)**: Generación de tokens criptográficos únicos por sesión validados en cada petición `POST`.
2. **Consultas Preparadas (PDO Prepared Statements)**: Mitigación total contra Inyección SQL (SQLi).
3. **Hashing Seguro de Contraseñas**: Uso del algoritmo nativo `PASSWORD_BCRYPT` de PHP con sales automáticas.
4. **Seguridad de Sesiones**: Configuración de `HttpOnly`, `SameSite=Lax` y regeneración periódica de identificador de sesión contra Session Fixation.
5. **Sanitización de Datos**: Escapado automático mediante `htmlspecialchars` en renderizado de vistas para prevención de XSS.
6. **Tipado Estricto**: Archivos PHP con directiva `declare(strict_types=1);` para consistencia y robustez de tipos.

---

⚽ **TOP GOL** - Pasión por el fútbol y tecnología de punta.