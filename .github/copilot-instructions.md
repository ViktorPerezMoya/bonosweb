# GitHub Copilot Instructions for BonosWeb

You are helping build BonosWeb, a multi-tenant SaaS for electronic payslip signatures. Always follow these rules and architectural constraints:

## 1. Stack & Directory Structure
- **Backend:** Laravel 12, Livewire 4, and TailwindCSS (configured with a dark-mode glassmorphism theme). Located in the `backend/` directory.
- **Frontend/Mobile:** Ionic 8, Angular 20, and Capacitor 8. Located in the `mobile-app/` directory.

## 2. Database Isolation & Tenancy
- We use `stancl/tenancy` for database isolation (one physical database per tenant).
- **Central Context:** Models (`Tenant`, `Domain`, `GlobalUser`, `GlobalSetting`, `TenantInvoice`, `TenantPayment`). Central migrations are in `database/migrations`.
- **Tenant Context:** Models (`User`, `EmployeeProfile`, `UploadBatch`, `Payslip`, `Signature`). Tenant migrations are in `database/migrations/tenant`.
- NEVER mix query scopes between central and tenant contexts unless explicitly requested.

## 3. Business Logic Details
- **Billing & Invoices:** Fixed monthly payment day (minimum 15th, adjusting to last day of month if needed, shifting weekends to the next business day using Carbon). Dynamically calculates billing balance, applies inflation adjustments if checked, generates invoices after the 15th, and auto-suspends tenants after 2 weeks of overdue balance.
- **PDF Signature Engine:** Uses `smalot/pdfparser` to parse employee CUIL for automatic mapping of uploaded ZIP batches. Uses `tecnickcom/tcpdf` for generating pdfs signed electronically using tenant-specific certificates (`cert_path` and `cert_key_path`).

## 4. UI Style Guide
- Backend layout features a dark mode with glassmorphic inputs. Use existing classes like `form-group`, `form-label`, `form-control`, `btn-primary`, and `user-profile-link` for hover navigation.
- Mobile App must align with Ionic 8 Angular 20 Standalone structure and reuse similar dark-mode visual elements.
