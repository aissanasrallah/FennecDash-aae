# FennecDash v3.4.1 — CodeIgniter 3 CRM

## PHP & Framework

- **PHP 8.1+ required** — enforced in `application/config/config.php:4`
- **CodeIgniter 3.1.11** (customized CI version check at `index.php:37`)
- Sequential DB migrations in `application/migrations/` (242 files, v101→341)
- Composer autoload enabled (`$config['composer_autoload'] = true`) in `application/vendor/`

## Developer Commands

| Command | What it does |
|---|---|
| `npm run dev` | Laravel Mix dev build (JS→assets/builds/app.js, Tailwind→assets/builds/tailwind.css) |
| `npm run prod` / `npm run build` | Production build + einvoice_disabled module + version header on minified JS |
| `npm run watch` | Watch mode for development |
| `npm run build:landing` | Standalone Tailwind for landing page (`tailwind.landing.config.js`) |

No test, lint, or typecheck commands exist.

## Architecture

- **App_Controller** → **AdminController** / **ClientsController** → specific controllers
- **App_Model** → specific models (47 models in `application/models/`)
- All base classes in `application/core/` (14 files)
- Services namespaced under `app\` in `application/services/`
- Custom autoloader at `application/hooks/App_Autoloader.php` handles `app\` and `modules\` namespaces

## Frontend / CSS

- **Bootstrap 3** markup throughout admin views (`.panel`, `.btn-default`, `.form-control`, `col-md-*`)
- **Tailwind v3** with `tw-` prefix and `preflight: false` to coexist with BS3 — `tailwind.config.js`
- **3 competing theme files**: `assets/css/style.css` (5044 lines), `vuexy.css` (904), `fennec.css` (2125)
- **Client theme**: `assets/themes/fennec/css/style.css` (3796 lines)
- **Separate Tailwind build** for landing: `tailwind.landing.config.js` — no prefix, dark mode via `class` strategy
- **Icons**: Font Awesome 5/6 (admin legacy), Tabler Icons (auth/theme/landing), Heroicons SVG (newer nav); `fennec-icons.css` maps FA→Tabler
- **Vue 3** mounted at `#vueApp` — only `AppFilters` component in `resources/js/components/filters/`
- **Component CSS** (`resources/css/*.css`) uses `@apply tw-...` directives

## Views / Layout

- **Admin**: `init_head($aside = true)` → page content → `init_tail()` pattern
- **Client**: Global variable buffering via `theme_head_view()` / `theme_template_view()` / `theme_footer_view()` — shell at `application/views/themes/fennec/index.php`
- **Auth**: Standalone pages with `authentication/includes/head.php`
- **Forms**: Full standalone HTML documents
- **Landing**: Single page at `application/views/landing/index.php`
- View customization: create `my_<filename>.php` alongside any view — auto-picked by `App_Loader`

## Module System (HMVC)

- 19 modules in `/modules/` — each with own controllers, models, views, migrations
- Entrypoint: `modules/<name>/<name>.php` — registers init hooks
- Powered by `MX_Loader` from `application/third_party/MX/`
- Init: `InitModules` hook → `app_modules->get_activated()` → require init file → `modules_loaded` action
- Skip via `?skip_modules_load=1` (admin only)

## Hooks & Override System

- **Hooks**: `hooks()` global (from `bainternet/php-hooks`), system hooks in `application/config/hooks.php`
- **Custom overrides**: `my_routes.php`, `my_hooks.php`, `custom_lang.php`, `custom.css` — all optional, auto-included if present

## Key Config (in `application/config/app-config.php`)

`APP_BASE_URL`, `APP_ENC_KEY`, `APP_DB_*` (hostname, username, password, name), `SESS_DRIVER` (default: `database`), `SESS_SAVE_PATH` (default: `sessions`), `APP_CSRF_PROTECTION` (default: `true`), `APP_DB_PREFIX` (default: `tbl`)

## Gotchas

- **CSRF is ON** by default — bypasses for forms/wtl, forms/ticket, forms/quote, gateways/ callbacks
- **Tailwind preflight is OFF** — don't expect resets; BS3 base styles remain
- **Two separate Tailwind configs** — admin uses `tw-` prefix, landing has no prefix
- **Session driver is database** by default (`sessions` table) — change to `files` in app-config.php if needed
- **Migration enabled is FALSE** by default in `application/config/migration.php:15`
- **Demo mode** (`DEMO_MODE`) blocks non-GET non-AJAX requests
- **Default controller** is `landing`, not admin — `/admin` maps to `admin/dashboard`
- **Environment** is `production` in `index.php:65` — change to `development` for errors
- **DB prefix** via `db_prefix()` returns `tbl` (all tables prefixed `tbl`)
