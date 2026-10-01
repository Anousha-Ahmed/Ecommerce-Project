# E-Commerce Store (PHP + MySQL)

A full-stack e-commerce website with a customer storefront and an admin panel, built with core PHP and MySQL (no framework).

## Features

### Customer Storefront
- Home page with category showcase and featured products
- Product listing with category filter and search
- Product detail page
- Shopping cart (add, update quantity, remove)
- Checkout with Cash on Delivery and Stripe (card payment)
- Order confirmation page
- Customer login / register / logout
- About Us and Contact Us pages

### Admin Panel
- Dashboard with store stats (products, categories, orders, customers, revenue)
- Product management (add, edit, list)
- Category management (add, edit, list)
- Order management (view details, update order/payment status)
- Customer list
- Admin login (separate from customer login)

## Tech Stack

- **Backend:** PHP (core, no framework)
- **Database:** MySQL
- **Frontend:** Molla eCommerce HTML template (storefront), Material Dashboard template (admin panel)
- **Payments:** Stripe Checkout

## Setup

1. Clone the repository into your local server's web folder (e.g. `htdocs` for XAMPP, `www` for WAMP).
2. Create a MySQL database and import `schema.sql`.
3. Copy `config/database.php` and set your database credentials (host, username, password, database name).
4. Copy `config/stripe.example.php` to `config/stripe.php` and add your Stripe **test** secret key from https://dashboard.stripe.com/test/apikeys.
5. Start Apache + MySQL and open:
   - Storefront: `http://localhost/<project-folder>/public/index.php`
   - Admin panel: `http://localhost/<project-folder>/admin/login.php`

## Folder Structure

\```
admin/          Admin panel pages (products, categories, orders, users, dashboard)
public/         Customer-facing storefront
core/           Shared PHP classes (Database, Auth, Session, Validator, Stripe)
config/         Database and Stripe configuration (not committed to git)
includes/       Shared layout files (header, footer, admin header/footer) and shared functions
\```

## Notes

- `config/stripe.php` is excluded from git via `.gitignore` to avoid exposing API keys. Use `config/stripe.example.php` as a template.
- Products or categories already referenced in an existing order cannot be deleted (to protect order history) — set their Status to **Inactive** instead to hide them from the store.