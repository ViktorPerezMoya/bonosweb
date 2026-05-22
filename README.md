# BonosWeb - Plataforma SaaS Multi-tenant de Liquidaciones y Firma Digital

BonosWeb es una solución SaaS diseñada para que empresas (tenants) administren a sus empleados y gestionen de forma segura la carga, previsualización y firma electrónica de recibos de sueldo/liquidaciones.

## 🛠️ Stack Tecnológico
* **Backend & API:** Laravel 12 (PHP 8.2+) con arquitectura Multi-tenant (`stancl/tenancy` con bases de datos aisladas).
* **Panel Web Central/Tenant:** Laravel Livewire 4 y TailwindCSS (temática oscura con Glassmorphism).
* **Aplicación Móvil (Empleados):** Ionic 8 (Angular 20 & Capacitor 8) para previsualización de PDFs y firma electrónica.
* **Procesamiento de PDFs:** `smalot/pdfparser` (OCR/parsing para asignación automática por CUIL) y `tecnickcom/tcpdf` (generación y sellado criptográfico con certificados digitales).
* **Base de Datos:** MySQL/PostgreSQL (Esquema Central para SuperAdmin, facturas y configuraciones; Esquemas de Tenants independientes para empleados, recibos y firmas).
