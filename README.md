# AS Berkah E-Commerce

Multi-role PHP + MariaDB e-commerce web application supporting storefront shopping, cart & transactional checkout, payment confirmation, warehouse fulfillment, courier dispatch, and admin operations.

## User Roles

- **Customer**: Browse catalog with live search & sorting, manage shopping cart, place orders with shipping address & payment method, complete payment, track order status, and manage account password.
- **Admin**: Monitor store KPIs, approve/reject staff & courier registrations, manage product inventory (CRUD + validated image uploads), confirm incoming orders, and inspect completed sales reports.
- **Employee**: Pack confirmed orders (`Dikonfirmasi` → `Dikemas` → `Siap Dikirim`).
- **Courier**: Pick up ready parcels and confirm delivery (`Siap Dikirim` → `Dikirim` → `Sampai Tujuan`).

## Quick Start (Docker — Recommended)

The included `Dockerfile` bundles Apache + PHP 8.2 + MariaDB and automatically initializes `ecommerce.sql` and `deploy/demo_seed.sql`:

```bash
docker build -t as-berkah-ecommerce .
docker run --rm -p 8080:10000 -e PORT=10000 as-berkah-ecommerce
```

Open `http://localhost:8080`.

### Demo Accounts (Seeded in Docker)

| Role | Username | Password |
| :--- | :--- | :--- |
| Admin | `admin` | `admin123` |
| Customer | `customer1` | `demo123` |
| Employee | `employee1` | `demo123` |
| Courier | `courier1` | `demo123` |

## Local Development (XAMPP / Apache + MySQL)

1. Import `ecommerce.sql` (and optionally `deploy/demo_seed.sql`) into MySQL/MariaDB.
2. Configure optional environment variables if your DB credentials differ from defaults (`DB_HOST=localhost`, `DB_USER=root`, `DB_PASS=`, `DB_NAME=ecommerce_db`).
3. Serve the project directory via Apache or `php -S localhost:8080`.
