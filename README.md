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

Everything runs in Docker: one command starts all six containers. A small front door (Caddy) gives the
store and the staff panel one address, which ngrok can share online.

```
                     Docker (network: shansguitar_network)
(both browsers go through shansguitar_proxy, the front door, on ports 8000 and 8080)
Customer's browser ──> shansguitar_store   (Apache + PHP 8.4, the store)   ──┐
                                                                           ├──> shansguitar_mysql (one database)
Staff's browser    ──> shansguitar_admin   (Apache + PHP 8.4, Symfony)     ──┘
                       shansguitar_phpmyadmin (database viewer)
                       shansguitar_mailpit    (catches the store's emails)
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

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) — that's all. PHP, Apache, Composer and
  MySQL all run inside the containers (no XAMPP needed).
- Git, to download the two repositories.

## Installation

The two repositories must sit **side by side**:

```
C:\shans_guitar1\shans_guitar          <- this project (staff panel + compose.yaml)
C:\shans_guitar1\shans_guitar_store    <- the customer store
```

```powershell
cd C:\shans_guitar1
git clone https://github.com/ZraeCS/final_shans_guitar_store.git shans_guitar
git clone https://github.com/ZraeCS/shans_guitar_store_web_app.git shans_guitar_store
cd shans_guitar

docker compose up -d --build                                                   # builds and starts all 5 containers
docker compose exec shansguitar_admin composer install                         # downloads the PHP libraries
docker compose exec shansguitar_admin php bin/console doctrine:migrations:migrate -n
docker compose exec shansguitar_admin php bin/console doctrine:fixtures:load -n
docker compose exec shansguitar_admin php bin/console tailwind:build --minify  # builds the CSS
docker compose exec shansguitar_admin php bin/console asset-map:compile        # production files in public/assets
docker compose exec shansguitar_admin php bin/console cache:clear
```

The panel runs in **production mode** (no debug pages). It needs a secret key in `.env.prod.local`
(git-ignored): `APP_SECRET=` followed by 32 random hex characters, for example from
`docker compose exec shansguitar_admin php -r "echo bin2hex(random_bytes(16));"`.

Wait about 20 seconds after the first `docker compose up` before migrating: MySQL needs a moment to start.
From then on, `docker compose up -d` starts everything and `docker compose stop` stops it.

### Commands inside Docker

The Symfony console runs **inside** the `shansguitar_admin` container:

| Instead of | Type |
|---|---|
| `symfony server:start` | nothing: the panel is always running at http://127.0.0.1:8000 |
| `symfony console <command>` | `docker compose exec shansguitar_admin php bin/console <command>` |
| `composer <command>` | `docker compose exec shansguitar_admin composer <command>` |
| After changing a template or CSS | `… php bin/console tailwind:build --minify`, then `… php bin/console asset-map:compile`, then `… php bin/console cache:clear` |
| After changing PHP code or config | `docker compose exec shansguitar_admin php bin/console cache:clear` |
| Reset the demo data | `docker compose exec shansguitar_admin php bin/console doctrine:fixtures:load --env=dev` (fixtures exist only in dev) |
| Development mode (debug toolbar) | add `PANEL_ENV=dev` and `PANEL_DEBUG=1` to `.env`, delete `public/assets`, `docker compose up -d` |

You still edit the code in VS Code as usual: the containers read the project folders directly, so a refresh
shows your change. Only a change to a `Dockerfile` needs `docker compose up -d --build`.

## Addresses

| What | Address |
|---|---|
| Staff panel | http://127.0.0.1:8000 (port 8000 = panel first) |
| Customer store | http://localhost:8080 (port 8080 = store first; `/login` there opens the panel too) |
| phpMyAdmin (the shared database) | http://localhost:8081 |
| Mailpit (emails sent by the store) | http://localhost:8080/mailpit/ (user `mailpit`, password `ShansMail2026`, changeable in `docker/proxy/Caddyfile`) |

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

## Online with ngrok (final exam)

1. Create a free account at ngrok.com, install the ngrok CLI (for example `scoop install ngrok`),
   and run once: `ngrok config add-authtoken <your token>`.
2. Note your free **dev domain** on the ngrok dashboard (Domains), e.g. `your-name.ngrok-free.app`,
   and add to `.env`: `PUBLIC_URL=https://your-name.ngrok-free.app` (used in reset-email links),
   then `docker compose up -d`.
3. Start everything: `docker compose up -d`, then in a second terminal: `ngrok http 8000`.
4. Share `https://your-name.ngrok-free.app`. It opens the Staff Log In; the store is at
   `/shans_guitar/` ("Back to the store" on the login page); the Mailpit inbox at `/mailpit/`.

The free plan shows a one-time "Visit Site" warning page to new visitors. Keep the laptop on, plugged in
and awake while the link must be reachable.

## Rebuilding the database (drop test)

```powershell
docker compose exec shansguitar_admin php bin/console doctrine:database:drop --force
docker compose exec shansguitar_admin php bin/console doctrine:database:create
docker compose exec shansguitar_admin php bin/console doctrine:migrations:migrate -n
docker compose exec shansguitar_admin php bin/console doctrine:fixtures:load -n --env=dev
```

After these four commands the admin panel and the store work again with the original data.
`-n` answers the yes/no questions automatically.

## Configuration

| Setting | Where | Default |
|---|---|---|
| Containers, ports, and the settings passed to both apps | `compose.yaml` | Store 8080, panel 8000, phpMyAdmin 8081, Mailpit 8025 (SMTP 1025), MySQL 3313 |
| Container recipes | `docker/store/`, `docker/admin/` | Apache + PHP 8.4 |
| Database connection | `DATABASE_URL` in `compose.yaml` (wins over `.env`) | root/root on `shansguitar_mysql:3306`, database `shansguitar` |
| Store address and folder | `STORE_BASE_URL`, `STORE_DIR` in `compose.yaml` | http://localhost:8080/shans_guitar, the store folder |
| Store folder on your computer | `STORE_PATH` environment variable | `../shans_guitar_store` (next to this project) |
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
| A page doesn't load at all | Start Docker Desktop, then `docker compose up -d`; `docker ps` should list 5 containers |
| `port is already allocated` (8000) | Something else uses port 8000, usually an old `symfony server:start`: run `symfony server:stop` or close that terminal |
| Panel shows "Oops! An Error Occurred" | `docker compose logs --tail=30 shansguitar_admin`; often a missing `.env.prod.local` (APP_SECRET) or a cache to clear |
| `port is already allocated` (8080) | Another web server uses 8080; stop it (or XAMPP's Apache if you changed its port) |
| Panel page without colors | `… tailwind:build --minify`, `… asset-map:compile`, `… cache:clear`, then Ctrl+F5 |
| Store shows "Could not connect to the database" | Wait 20 seconds after starting (MySQL is starting), then refresh |
| The store page is empty / 404 | The store folder isn't at `..\shans_guitar_store`; move it there (or set `STORE_PATH`) and run `docker compose up -d` |
| See what went wrong inside a container | `docker compose logs shansguitar_admin` (or `shansguitar_store`) |
| Strange errors after pulling changes | `docker compose exec shansguitar_admin composer install`, then `... php bin/console cache:clear` |

### Running without Docker (fallback)

The code still works the old way: the store in XAMPP (`htdocs\shans_guitar`, uses 127.0.0.1:3313) and the panel with
`symfony server:start`, as long as the database containers are running. The settings fall back to those values
automatically when Docker doesn't provide them.
