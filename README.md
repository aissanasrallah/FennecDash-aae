# FennecDash

FennecDash is a CodeIgniter 3 CRM for vehicle export, logistics, accounting, and customer management. It includes the **Auto Export (AAE)** module, which covers the full export workflow: quotation, order, invoice, supplier payment, shipping, customs, delivery, final papers, and settlement.

## FennecDash MCP (AI agent integration)

This repository also ships **`fennedash-mcp`**, a vendor-independent Model Context Protocol server that exposes the CRM to AI agents over stdio and Streamable HTTP. It is a separate Node.js 20+ package and talks to FennecDash only through the additive Agent API in `application/controllers/api/`; it never connects to MySQL directly or bypasses `staff_can()` permissions.

- 59 typed tools with READ / WRITE / CRITICAL risk classes, 8 resources, 2 URI templates and 5 prompts.
- Bearer tokens bound to a staff account, stored as SHA-256 hashes, with a risk ceiling, optional feature scopes, expiry and revocation.
- Dry-run plus single-use, payload-bound approvals for the one CRITICAL operation (cache clearing).
- Two audit trails, secret redaction, and a SQL escape hatch that is disabled by default.

```bash
cd fennedash-mcp
npm install
cp .env.example .env    # set FENNEDASH_BASE_URL and FENNEDASH_API_TOKEN
npm run build
npm run mcp:doctor
```

Documentation: [MCP README](fennedash-mcp/README.md) · [Installation](fennedash-mcp/INSTALLATION.md) · [Configuration](fennedash-mcp/CONFIGURATION.md) · [Security model](fennedash-mcp/docs/SECURITY_MODEL.md) · [Tool catalog](fennedash-mcp/docs/MCP_TOOL_CATALOG.md) · [Client guides](fennedash-mcp/integrations/).

## Requirements

- PHP **8.1+** (tested with PHP 8.4)
- MySQL 5.7+ / MariaDB 10.3+
- Apache with `mod_rewrite` (or Nginx with an equivalent rewrite rule)
- Node.js 18+ and npm (only needed to rebuild front-end assets)

## Installation

### 1. Clone the repository

```bash
git clone <repository-url> fennecdash
cd fennecdash
```

### 2. Create the local environment file

```bash
cp .env.example .env
```

Edit `.env` and set at least:

| Variable | Description |
|---|---|
| `APP_BASE_URL` | Full application URL, with a trailing slash |
| `APP_ENC_KEY` | 32-character encryption key |
| `APP_DB_HOSTNAME` | Database host and port (e.g. `localhost:3306`) |
| `APP_DB_USERNAME` | Database user |
| `APP_DB_PASSWORD` | Database password |
| `APP_DB_NAME` | Database name |

Generate a new encryption key with:

```bash
php -r "echo bin2hex(random_bytes(16));"
```

> **Important:** never commit `.env`. It is already listed in `.gitignore`.

`application/config/app-config.php` reads these values from the environment. It contains no credentials and is safe to commit.

### 3. Create the database

Create an empty database, then import the schema:

```bash
mysql -u root -p your_database < installd/database.sql
```

Alternatively, open `/installd` in a browser and use the installer. **Remove or block `installd/` after installation.**

### 4. Set permissions

The web server must be able to write to:

```text
uploads/
media/
temp/
application/cache/
application/logs/
sessions/          # only when SESS_DRIVER=files
```

### 5. Build front-end assets (optional)

Compiled assets are committed under `assets/builds/`. Rebuild them only when changing JavaScript, Tailwind, or Vue sources:

```bash
npm install
npm run dev
```

For production:

```bash
npm run build
```

### 6. Open the application

```text
http://localhost/FennecDash5.4/admin
```

The default controller is the public landing page. `/admin` opens the staff dashboard.

## Security notes

- **Never commit `.env`** or any real API token, SMTP password, database password, or encryption key.
- `application/config/app-config.php` is environment-driven and must stay free of secrets.
- `application/vendor/` (and the module `vendor/` folders) are committed so the application can be deployed without running Composer. If you prefer a smaller repository, run `composer install` in `application/` and remove the vendor folders from version control.
- Remove or deny access to `installd/` after installation.
- If you use the web installer, it may rewrite `application/config/app-config.php` with literal credentials. Move those values into `.env`, then restore the tracked file with `git checkout -- application/config/app-config.php`.
- Use HTTPS in production and keep `APP_COOKIE_HTTPONLY=true`.
- CSRF protection is enabled by default (`APP_CSRF_PROTECTION=true`).
- The WhatsApp webhook is intentionally excluded from CSRF because Meta posts to it without a token. It still validates the webhook verify token.
- Backups and database dumps are ignored by Git. Keep them outside the repository.

## Project structure

```text
application/          CodeIgniter application (controllers, models, views, config)
  config/             Configuration; app-config.php reads .env
  core/               Base controllers and models
  migrations/         Sequential database migrations
  models/             CRM models
  services/           Namespaced services (app\)
  vendor/             Vendored Composer dependencies
assets/               Admin/client CSS, JavaScript, and compiled builds
modules/              HMVC modules, including modules/aae (Auto Export)
resources/            Vue and Tailwind sources
system/               CodeIgniter 3 core
uploads/              Runtime customer uploads (files ignored by Git)
installd/             Web installer and base schema
```

## Auto Export module

The AAE module is available at `/admin/aae`. It provides:

- Vehicle catalogue and price calculation (EUR/DZD, markup, fees, commission)
- Export orders with one or more vehicles per dossier
- 15 workflow stages plus cancellation
- Quotes, invoices, contracts, and accounting links
- Client and supplier payments
- Shipping, customs, and delivery documents
- WhatsApp click-to-chat and optional WhatsApp Cloud API notifications
- Public estimate links for customers
- Staff dashboard widgets: export pipeline and active-order finance

Configure WhatsApp and e-mail delivery from **Auto Export → Settings**. Credentials are stored in the database, not in source control.

## Development

```bash
npm run watch      # rebuild assets while developing
npm run build      # production build
```

There are no automated test, lint, or typecheck commands in this repository.

## License

No license has been specified for this project. All rights reserved unless a license is added.
