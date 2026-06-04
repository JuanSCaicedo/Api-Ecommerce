<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

cat > /mnt/user-data/outputs/README_Api-Ecommerce.md << 'EOF'
# Api-Ecommerce

REST API para una plataforma de comercio electrónico orientada a MiPymes colombianas. Desarrollada como proyecto de grado en la Universidad ECCI (2025), esta API centraliza toda la lógica de negocio del sistema: autenticación, catálogo, carrito, checkout, pagos y administración.

> 📄 Documentación técnica completa generada con DeepWiki: [deepwiki.com/JuanSCaicedo/Api-Ecommerce](https://deepwiki.com/JuanSCaicedo/Api-Ecommerce)  
> 🛒 Demo desplegado: [ecommerce.juandevops.com](https://ecommerce.juandevops.com)

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Lenguaje | PHP 8.2 |
| Framework | Laravel 10 |
| Base de datos | MySQL 8.0 |
| Autenticación | JWT (JSON Web Tokens) |
| Almacenamiento | Local / OCI Object Storage |
| Pagos | MercadoPago SDK + PayPal API |
| Notificaciones | SMTP / API de correo |
| CI/CD | GitHub Actions |
| Infraestructura | Oracle Cloud Infrastructure (VPS Ubuntu) |
| Proxy / Seguridad | Cloudflare |

---

## Arquitectura

La API sigue una arquitectura **cliente-servidor monolítica** con separación clara de capas:

```
┌─────────────────────────────────────┐
│         Frontend (Angular)          │
│   Tienda cliente + Panel Admin      │
└───────────────┬─────────────────────┘
                │ HTTP/HTTPS (JSON)
┌───────────────▼─────────────────────┐
│           API Laravel               │
│  Controladores → Servicios → ORM    │
│  Middlewares (Auth JWT, Rate Limit) │
└───────────────┬─────────────────────┘
                │
┌───────────────▼─────────────────────┐
│           MySQL 8.0                 │
│  Usuarios · Productos · Órdenes     │
│  Inventario · Tokens · Config       │
└─────────────────────────────────────┘
```

Patrón arquitectónico: **MVC** con controladores independientes por módulo, validadores externos, middlewares de autenticación y servicios internos desacoplados.

---

## Módulos de la API

### Autenticación (`/api/auth`)
- Registro de clientes con verificación de correo
- Login con emisión de JWT diferenciado por rol (cliente / administrador)
- Recuperación y restablecimiento de contraseña
- Logout e invalidación de token

### Usuarios (`/api/users`)
- Gestión del perfil del cliente
- Administración de direcciones de envío (CRUD)
- Listado y gestión de usuarios desde el panel admin

### Catálogo y Productos (`/api/products`, `/api/categories`, `/api/brands`)
- CRUD completo de productos con imágenes
- Gestión de categorías y marcas
- Filtros por categoría, marca y búsqueda
- Control de inventario por producto

### Carrito de Compras (`/api/cart`)
- Agregar, actualizar y eliminar ítems
- Cálculo de totales en tiempo real
- Persistencia del carrito por usuario autenticado

### Checkout y Pagos (`/api/checkout`, `/api/payments`)
- Creación de órdenes desde el carrito
- Integración con **MercadoPago**: generación de preferencias de pago y recepción de webhooks
- Integración con **PayPal**: creación de órdenes y captura de pagos
- Actualización automática del estado de la orden según respuesta del proveedor

### Ventas y Órdenes (`/api/orders`)
- Consulta del historial de compras del cliente
- Gestión y actualización de estado de órdenes (admin)
- Detalle de cada venta con productos, cantidades y montos

### Administración (`/api/admin/*`)
- Dashboard con métricas básicas del negocio
- Gestión de promociones y descuentos
- Configuración dinámica del sitio (sliders, contenido de inicio, estados)
- Gestión de usuarios internos con roles y permisos

---

## Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/       # Controladores por módulo
│   ├── Middleware/        # Auth JWT, rate limiting, roles
│   └── Requests/          # Validaciones externas por endpoint
├── Models/                # Modelos Eloquent (User, Product, Order...)
├── Services/              # Lógica de negocio desacoplada
routes/
├── api.php                # Definición de todos los endpoints REST
database/
├── migrations/            # Estructura completa de la BD
└── seeders/               # Datos de prueba
```

---

## Base de datos

Las entidades principales del sistema:

| Tabla | Descripción |
|-------|-------------|
| `users` | Clientes y administradores con roles |
| `products` | Catálogo con precio, stock e imágenes |
| `categories` / `brands` | Clasificación del catálogo |
| `carts` / `cart_items` | Estado del carrito por usuario |
| `orders` / `order_items` | Órdenes de compra y su detalle |
| `addresses` | Direcciones de envío del cliente |
| `promotions` | Descuentos aplicables al checkout |
| `settings` | Configuración dinámica del sitio |
| `personal_access_tokens` | Tokens JWT activos |

---

## Seguridad

- Contraseñas cifradas con **bcrypt**
- Autenticación stateless con **JWT**, separada por rol
- Validación estricta del lado servidor en todos los endpoints
- **Rate limiting** para mitigar abusos y ataques automatizados
- Proxy inverso con **Cloudflare** para protección contra bots y tráfico malicioso
- Comunicación exclusiva vía **HTTPS** con certificado TLS

---

## Formato de respuestas

Todas las respuestas siguen una estructura JSON uniforme:

```json
{
  "success": true,
  "data": { ... },
  "message": "Descripción del resultado",
  "errors": null
}
```

---

## Requisitos

- PHP >= 8.2
- Composer
- MySQL 8.0
- Node.js v18 (solo para compilar frontend en caso de monolito)

---

## Instalación local

```bash
git clone https://github.com/JuanSCaicedo/Api-Ecommerce.git
cd Api-Ecommerce
composer install
cp .env.example .env
php artisan key:generate
# Configurar DB, mail y credenciales de pago en .env
php artisan migrate --seed
php artisan serve
```

---

## CI/CD y despliegue

El proyecto incluye un pipeline con **GitHub Actions** que automatiza:
1. Validación del código en cada push
2. Despliegue automático al VPS de Oracle Cloud Infrastructure
3. Cloudflare como capa de proxy y protección en producción

---

## Repositorios relacionados

| Repositorio | Descripción |
|-------------|-------------|
| [Front-Ecomerce](https://github.com/JuanSCaicedo/Front-Ecomerce) | Tienda online (Angular 17) |
| [Admin-EcommerceE](https://github.com/JuanSCaicedo/Admin-EcommerceE) | Panel administrativo (Angular 16) |

---

## Contexto académico

Este proyecto fue desarrollado como **trabajo de grado** para optar al título de Tecnólogo en Desarrollo Informático en la Universidad ECCI (Bogotá, 2025). El objetivo fue construir un prototipo funcional de plataforma de ecommerce adaptada a las necesidades de las MiPymes colombianas, cubriendo el ciclo completo: análisis de requerimientos, diseño de arquitectura, desarrollo, pruebas y despliegue en producción.

EOF
echo "README API creado"
).
