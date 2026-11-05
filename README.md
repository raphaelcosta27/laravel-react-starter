# Laravel + React Starter

[![CI](https://github.com/raphaelcosta27/laravel-react-starter/actions/workflows/ci.yml/badge.svg)](https://github.com/raphaelcosta27/laravel-react-starter/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

[Leia em português](README.pt-BR.md)

A base for new projects with Laravel 13, React 19, Inertia 2 and MySQL. It includes authentication, account
confirmation by email, a mandatory password change on first access, and automated tests. The interface ships
in Brazilian Portuguese.

## Requirements

- PHP 8.3 or later, with `pdo_mysql`, `pdo_sqlite` and `zip`
- Composer 2
- Node 22.12 or later
- MySQL 8 running (on WAMP, just start the services)

## Installation

```bash
composer setup
composer dev
```

`composer setup` installs the dependencies, creates `.env`, builds the frontend, and creates the database and
the initial user. It can be run again without losing data. `composer dev` serves the application at
<http://127.0.0.1:8000> with frontend hot reload.

## First access

By default, emails are not sent. They are written to `storage/logs/laravel.log`.

1. Open the log and copy the temporary password and the link from the welcome email for `admin@example.com`.
2. Open the link and sign in with the temporary password.
3. Set a new password.

The temporary password and the link are valid for 1 hour. After that, use **Solicitar nova autenticação** on
the login screen to receive a new password and a new link.

## Creating users from the terminal

```bash
php artisan app:create-user
```

The command asks for a name, an email and a password (twice), with the same rules as web registration. The
account is confirmed from the start and no email is sent. Like every new user, this user sets a new password
on first access.

## Tests

```bash
composer test   # backend
npm test        # frontend and accessibility
```

Backend tests use in-memory SQLite and never touch the development database. For that reason, migration
changes must also be checked on MySQL with `php artisan app:setup`.

## Configuration

Everything that varies between projects lives in `.env`. The main options:

| Variable | Default | Purpose |
|----------|---------|---------|
| `APP_NAME` | `Template` | Name shown on screens and in emails |
| `APP_URL` | `http://127.0.0.1:8000` | Address used in email links |
| `SERVER_PORT` | `8000` | Local port; must match `APP_URL` |
| `APP_LOGO` | `/images/logo.svg` | Application logo |
| `APP_FAVICON` | `/favicon.ico` | Browser icon |
| `DB_DATABASE` | `template` | Database name |
| `DB_ENGINE` | `InnoDB` | MySQL engine; the WAMP default, MyISAM, does not work |
| `MAIL_MAILER` | `log` | How emails are delivered |
| `REGISTRATION_ENABLED` | `false` | Enables the public registration screen |
| `TEMPORARY_PASSWORD_EXPIRE` | `60` | Validity of the temporary password and link, in minutes |
| `INITIAL_USER_EMAIL` | `admin@example.com` | Initial user's email; empty skips creating the user |
| `INITIAL_USER_PASSWORD` | empty | Temporary password; empty generates one automatically |
| `ADMIN_PROFILE_NAME` | `Administrador` | Name of the master profile, used only on creation |
| `DEFAULT_PROFILE_NAME` | `Usuário` | Name of the default profile, used only on creation |
| `ADMIN_EMAIL_THROTTLE` | `3` | Panel emails per target user, per window |
| `ADMIN_EMAIL_THROTTLE_MINUTES` | `10` | Length of that window, in minutes |
| `EXPORT_CSV_DELIMITER` | `;` | Column separator of exported CSV files |

Interface texts live in `lang/pt_BR.json` and `lang/pt_BR/`. The full list of options is in `.env.example`.

## Account rules

- Every user is created through `App\Actions\CreateUser`.
- The `must_change_password` field forces a password change. To request a new change from a user, call
  `$user->requirePasswordChange()`.
- New authenticated routes must use the `account.ready` middleware group. A test fails if any route is left
  out.

## Admin panel

The panel manages profiles, permissions and users. Setup creates the master profile, the default profile and
the 20 panel permissions, and the first user created in the system becomes an administrator.

**Checking a permission.** A permission is identified by its code, and the same check works everywhere:

```php
$user->can('cadastrar_usuario');

Route::get('reports', ReportController::class)->middleware(['account.ready', 'can:gerar_relatorio']);
```

```jsx
const can = useCan();

{can('gerar_relatorio') && <Link href="/reports">Reports</Link>}
```

`useCan` only shows or hides elements: protection always happens on the server. Administrators pass every
check, and a code that does not exist denies everyone else. Rules that also bind administrators belong in
Actions, because `Gate::before` skips policies for them.

**Deleting data.** Nothing is physically deleted. Every entity uses soft deletes, including links, and a test
fails if `forceDelete`, `detach`, `sync` or `truncate` appears in `app/` or `database/`.

**Lock tests.** They need real row locks, so they run on MySQL, in a disposable database that is wiped:

```bash
php vendor/bin/phpunit -c phpunit.mysql.xml
```

### DataTable

Every listing uses `resources/js/Components/DataTable`. Sorting, search, filters, pagination and export run on
the server, through a definition in `app/Support/Tables/Definitions`, and only declared columns reach the
query. The user's choices are kept in the browser.

```jsx
<DataTable
    tableKey="reports"
    caption={__('Reports')}
    columns={columns}
    rows={rows}
    query={query}
    tabs={tabs}
    rowActions={rowActions}
    exportRoute="admin.reports.export"
    canExport={abilities.export}
    emptyMessage={__('No reports found.')}
/>
```

`rowActions` receives the record and returns its buttons, each one with its own function:

```jsx
const rowActions = () => [
    {
        key: 'send',
        label: __('Send contract'),
        onClick: (row) => router.post(route('reports.send', row.id)),
        visible: (row) => row.can.send,
        confirm: { title: __('Send contract'), message: __('Confirm the sending?') },
        menu: true,
    },
];
```

The server sends `can` with each record, combining authorization and the state of the record. The full
contract of the component is documented in its props and in `TableDefinition`.

## Starting a new project

1. Click **Use this template** on GitHub to create your repository, then clone it.
2. Copy `.env.example` to `.env` and change `APP_NAME`, `APP_LOGO`, `DB_DATABASE` and `INITIAL_USER_EMAIL`.
3. Run `composer setup` and `composer dev`.

No application code needs to change.

## License

Released under the [MIT License](LICENSE).
