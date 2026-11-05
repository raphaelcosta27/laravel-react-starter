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

By default, emails are not sent. They are written to the log file of the day, such as `storage/logs/laravel-2026-11-05.log`.

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
| `APP_PRIMARY_COLOR` | `#3949ab` | Main interface color, hexadecimal and in quotes |
| `APP_SECONDARY_COLOR` | `#00695c` | Secondary interface color, hexadecimal and in quotes |
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
| `LOG_DAILY_DAYS` | `14` | Days log files are kept |

Interface texts live in `lang/pt_BR.json` and `lang/pt_BR/`. The full list of options is in `.env.example`.

## Logs

Each day has its own file in `storage/logs`, named `laravel-YYYY-MM-DD.log`. The day follows the timezone in
`config/app.php`, which is UTC by default. `php artisan app:clean-logs` deletes the files older than
`LOG_DAILY_DAYS`. Only files with that exact name are deleted.

The command runs every day through the Laravel scheduler, which must be active on the server. Run it with the
same user as the web server: otherwise, the site may lose permission to write to the file of the day.

On Linux, add this line to the crontab of that user:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

On Windows, create a Task Scheduler task that runs `php artisan schedule:run` every minute, in the project folder.

Existing installations must change `LOG_STACK=single` to `LOG_STACK=daily` in their `.env`. A previous
`laravel.log` is neither moved nor deleted.

## Change history

The `change_history` table keeps one item per changed field, with the operation, table, record, field, old and
new values, date and responsible user. Without a logged user, the source is `system`. There is no screen: query
it through the `App\Models\ChangeHistory` model. Items cannot be changed or deleted by the application.

```php
ChangeHistory::of($user)->where('field', 'email')->latest('id')->first()->actor;
```

A new entity adopts it with one declaration, and a test fails if an entity is left out:

```php
use RecordsChanges, SoftDeletes;
```

- `HISTORY_MASKED_FIELDS` lists the fields shown as `********`, added to the `$hidden` fields of each model.
- `HISTORY_IGNORED_FIELDS` lists the fields left out. By default, everything is recorded.
- Mass `update()`, `delete()` and `restore()` save each row, so they are recorded row by row.
- `insert()`, `upsert()`, `increment()`, `decrement()`, `truncate()`, `DB::table()`, `withoutEvents()` and the
  `Quietly` methods skip the history. A test fails if the application or the seeders use them.

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

**Granting permissions.** Non-administrators only grant what they hold, whether through an individual adjustment,
a profile's permissions, a new user or a profile change. What counts is what would become effective: keeping what
already exists and revoking stay allowed. Screens disable what cannot be granted.

**Adding a restricted function.** Add the code to `lang/pt_BR/permissions.php`, which feeds the seeder, and protect
the route with `can:`. The seeder only creates missing permissions. `PermissionCoverageTest` fails if a route has
no permission and is not a listed exception, or if a code in use is missing from the seeder.

**Deleting data.** Nothing is physically deleted. Every entity uses soft deletes, including links, and a test
fails if `forceDelete`, `detach`, `sync` or `truncate` appears in `app/` or `database/`.

**Lock tests.** They need real row locks, so they run on MySQL, in a disposable database that is wiped:

```bash
php vendor/bin/phpunit -c phpunit.mysql.xml
```

### Interface

Screens use [Material UI](https://mui.com/material-ui/), in its free version. The theme is in `resources/js/theme.js`; an
invalid color falls back to the default.

- `ActionIconButton`: icon action with a tooltip that shows the title and a short description, by mouse, keyboard
  or long press.
- `FormDialog`: create and edit form in a dialog. It fills the screen below 600 px and asks for confirmation before
  discarding changes.

Forms have their own address, such as `/admin/users/5/edit?search=ana`, which opens the dialog over the listing.
Saving or closing keeps filters, order and page.

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
