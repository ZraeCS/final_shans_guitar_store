# Shan's Guitar — Admin Panel (Symfony)

CSC 304 Web Programming — final project.

The staff panel of **Shan's Guitar**, a guitar store in Dumaguete City. It has two roles:
**Admin** and **Staff**. Staff manage products (their own), handle orders, verify GCash
payments and view customers; admins can do all of that plus see the dashboard and sales
reports and manage every user account.

It shares **one database** with the customer website (the PHP store,
[shans_guitar_store_web_app](https://github.com/ZraeCS/shans_guitar_store_web_app)):
an order placed in the store appears here immediately, and every change made here
(price, stock, order status, GCash verification) shows up in the store right away.

```
Customer's browser ──> Store (XAMPP, PHP)  ──┐
                                             ├──> MySQL (Docker)  +  Mailpit (Docker)
Admin's browser   ──> Admin (Symfony)      ──┘
```

## Features

| Page | Who | What it does |
|---|---|---|
| **Dashboard** (`/dashboard`) | Admin | Total users, total staff, product and order records; today's sales vs yesterday, orders today, GCash payments to verify, low-stock alert, recent orders with one-click actions |
| **Products & Stock** | Staff, Admin | Search and filter, add a product in a popup form, edit, photo upload (saved in the store's folder), − / + stock buttons, delete with confirmation. **Staff may change only the products they added; admins may change any** |
| **Orders** | Staff, Admin | Filter by status, search by order number or customer, order details with progress bar, confirm → ship → deliver, cancel (stock goes back) |
| **GCash Payments** | Staff, Admin | Queue of reference numbers to check in the GCash app, Verify / Reject, recently verified list (who and when) |
| **Sales Reports** | Admin | Today / 7 days / 30 days / this month / all time / custom dates, revenue per day or month, best sellers, payment methods, orders by status, CSV download |
| **Customers** | Staff, Admin | Everyone who signed up on the store, with orders and total spent, and each customer's order history |
| **Users** | Admin | Every account (name, email, role, status, date created), filter and search; **create** Staff or Admin accounts (registration), **edit** name / email / role / status, **reset password**, **disable / enable**, **delete with confirmation** |
| **My account** | Staff, Admin | Your profile and **change your password** |
| **Login** | Staff, Admin | Active staff and admin accounts only, remember me, logout, forgot password (handled by the store) |

## Roles and access control

| | Customer (`user`) | Staff (`staff`) | Admin (`admin`) |
|---|---|---|---|
| Store (shop, cart, checkout, account) | ✅ | — | — |
| Panel login | ❌ refused | ✅ | ✅ |
| Products: view, add | — | ✅ | ✅ |
| Products: edit, restock, delete | — | **own only** | any |
| Orders, GCash, Customers | — | ✅ | ✅ |
| My account (profile, password) | — | ✅ | ✅ |
| Dashboard, Sales Reports, Users | — | ❌ Access denied | ✅ |

How it is enforced, in three layers:

1. **`config/packages/security.yaml`**: `role_hierarchy` (ROLE_ADMIN includes ROLE_STAFF) and
   `access_control` (`^/dashboard$` and `^/dashboard/(users|reports)` need ROLE_ADMIN, the rest of
   `/dashboard` needs ROLE_STAFF).
2. **Controllers**: `#[IsGranted('ROLE_ADMIN')]` / `#[IsGranted('ROLE_STAFF')]` on every controller,
   and `GuitarVoter` (`src/Security/Voter/`) for "own records only".
3. **Templates**: the sidebar and the Edit / Delete buttons use `is_granted()`.

A logged-in user who opens a page they may not use (for example staff typing `/dashboard/users`)
sees **"Access denied"** and is sent to their own home page (`src/Security/AccessDeniedHandler.php`).
Disabled accounts can't log in, and a logged-in user who gets disabled is signed out on their next click.

## Built with

Symfony 8.1 · PHP 8.4+ · Doctrine ORM (MySQL) · Twig · Tailwind CSS (symfonycasts/tailwind-bundle) ·
Docker Compose (MySQL, phpMyAdmin, Mailpit)

## Requirements

- PHP 8.4 or newer with the `pdo_mysql` extension (XAMPP's PHP works)
- [Composer](https://getcomposer.org) and the [Symfony CLI](https://symfony.com/download)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- XAMPP's **Apache**, to run the customer store (only Apache; XAMPP's MySQL is not used)

## Installation

```powershell
git clone https://github.com/ZraeCS/final_shans_guitar_store.git
cd final_shans_guitar_store

composer install                               # downloads the libraries into vendor/
docker compose up -d                           # starts MySQL, phpMyAdmin and Mailpit
symfony console doctrine:migrations:migrate    # creates the tables (answer yes)
symfony console doctrine:fixtures:load         # fills them with the store's data (answer yes)
symfony console tailwind:build                 # builds the CSS
symfony server:start                           # http://127.0.0.1:8000
```

Wait about 20 seconds after `docker compose up -d` the first time: MySQL needs a moment to start.

While working on the design, keep `symfony console tailwind:build --watch` running in a second terminal.

## Addresses

| What | Address |
|---|---|
| Admin panel | http://127.0.0.1:8000/dashboard |
| Customer store | http://localhost/shans_guitar/shop.php |
| phpMyAdmin (the shared database) | http://localhost:8081 |
| Mailpit (emails sent by the store) | http://localhost:8025 |

## Accounts

The fixtures copy the accounts of the original store, **with their original passwords**:

| Email | Role | Password |
|---|---|---|
| admin@shansguitar.com | Admin | the original store admin password |
| mika@shansguitar.com | Staff (active) | `staff12345` (demo) |
| paolo@shansguitar.com | Staff (**disabled**, to show the status feature) | `staff12345` (demo) |
| hochimin@gmail.com, johncruz1@gmail.com | Customers (log in on the store) | their original passwords |

Mika added two products (Ernie Ball Slinky, Fender Deluxe Gig Bag), so the demo shows staff
editing their own records while the other products are view-only. New accounts are created in
**Users → Add staff / admin**.
Forgot a password? Use "Forgot password?" on either login page; the email arrives in Mailpit.

## Rebuilding the database (drop test)

```powershell
symfony console doctrine:database:drop --force
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate -n
symfony console doctrine:fixtures:load -n
```

After these four commands the admin panel and the store work again with the original data.
`-n` answers the yes/no questions automatically.

## Configuration

| Setting | Where | Default |
|---|---|---|
| Database connection | `.env` (`MYSQL_*` and `DATABASE_URL`) | root/root on 127.0.0.1:3313, database `shansguitar` |
| Containers, ports | `compose.yaml` | MySQL 3313, phpMyAdmin 8081, Mailpit 8025 (SMTP 1025) |
| Store address and folder | `config/services.yaml` (`STORE_BASE_URL`, `STORE_DIR`) | http://localhost/shans_guitar, C:/xampp/htdocs/shans_guitar |
| Timezone | `src/Kernel.php` | Asia/Manila |

To use other values on your computer without changing committed files, put them in `.env.local`.

## Database

Tables follow the project ERD (see the ERD in the course submission):

| Table | Entity | Notes |
|---|---|---|
| `users` | `User` | Customers, staff and admins, told apart by `role` (`user` / `staff` / `admin`); `status` = `active` / `disabled` |
| `guitars` | `Guitar` | Guitars and accessories; `item_code` is unique; `created_by` → `users` (who added it) |
| `orders` | `CustomerOrder` | `ORDER` is a reserved SQL word, so the class has a different name; `payment_verified_by` → `users` |
| `order_items` | `OrderItem` | One row per product in an order, with the price at the time of purchase |
| `cart_items` | `CartItem` | Saved cart lines (one row per user and product) |
| `password_reset_tokens` | `PasswordResetToken` | Forgot-password links; only a SHA-256 hash of the token is stored |

## Project structure (main folders)

```
src/
  Controller/      one controller per page (Home = dashboard, Product, Order, Gcash,
                   Report, Customer, User, Account, Registration, Login)
  Entity/          the 6 tables above
  Enum/            fixed lists: order status, payment method, availability, user role
  Form/            GuitarType, RegistrationFormType, UserEditType, AdminResetPasswordType,
                   ChangePasswordFormType
  Repository/      database queries (QueryBuilder), e.g. findLowStock(), getSalesBetween()
  Security/        LoginAuthenticator, StaffUserChecker (active staff/admins only),
                   AccessDeniedHandler, Voter/GuitarVoter (own records only)
  Service/         OrderWorkflow (order status rules), SalesReport (report numbers)
  Twig/            sidebar badge counts, links to the store
  DataFixtures/    the store's data, converted to the ERD
templates/
  base.html.twig   layout: sidebar + page header
  partials/        sidebar, icons, order pieces shared by several pages
  home/ product/ order/ gcash/ report/ customer/ user/ account/ registration/ security/
migrations/        the SQL that creates the tables
compose.yaml       Docker: MySQL, phpMyAdmin, Mailpit
```

## Course lessons covered

| Lesson | Where in this project |
|---|---|
| Creating controllers | `HomeController` (served at `/dashboard`), sidebar in `templates/partials/sidebar.html.twig`, layout in `base.html.twig` |
| Entity CRUD | 6 entities with relations, migration, create / read / update / delete for products |
| Form manipulation & repository | Add-product form drawn field by field in a popup (`product/index.html.twig`), custom QueryBuilder methods in `src/Repository/` |
| User entity, login & register | `User` entity, `LoginAuthenticator`, logout, remember me, `RegistrationController` + `RegistrationFormType` (staff and admin accounts, hashed passwords), roles and access control |

## Troubleshooting

| Problem | Fix |
|---|---|
| `Connection refused` / `SQLSTATE[HY000] [2002]` | Docker isn't running: start Docker Desktop, then `docker compose up -d` |
| Page without colors | Run `symfony console tailwind:build`, then refresh with Ctrl+F5 |
| Product photos missing | Start Apache in XAMPP (photos come from the store's folder) |
| No email in Mailpit | Check that `shansguitar_mailpit` is listed by `docker ps` |
| Strange errors after pulling changes | `composer install`, then `symfony console cache:clear` |
