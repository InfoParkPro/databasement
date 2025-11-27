# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel application for managing database server backups. It uses Livewire for reactive components and Mary UI (built on daisyUI and Tailwind CSS). The application allows users to register database servers (MySQL, PostgreSQL, MariaDB, SQLite), test connections, and manage backup configurations.

## Technology Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire, Mary UI (robsontenorio/mary), daisyUI, Vite, Tailwind CSS 4
- **Authentication**: Laravel Fortify (with two-factor support)
- **Testing**: Pest PHP
- **Database**: SQLite (development), supports managing MySQL, PostgreSQL, MariaDB servers
- **Development Tools**: Laravel Pail (logs), Laravel Pint (formatting), PHPStan (static analysis), Husky (git hooks)

## Development Commands

### Setup and Installation
```bash
make setup              # Full project setup: install deps, env setup, generate key, migrate, build assets
make install            # Install composer and npm dependencies only
```

### Running the Application
```bash
make start              # Start all services concurrently: server, queue, logs, vite (composer dev)
composer dev            # Alternative: runs concurrently with colored output
php artisan serve       # Run server only (http://localhost:8000)
```

### Testing
```bash
make test                           # Run all Pest tests
make test-filter FILTER=DatabaseServer  # Run specific test class/method
make test-coverage                  # Run tests with coverage report
make backup-test                    # Run end-to-end backup and restore tests (requires Docker containers)
php artisan test                    # Direct artisan command
php artisan backup:test             # Direct backup test command
php artisan backup:test --type=mysql     # Test specific database type only
```

**IMPORTANT**: When modifying backup or restore logic (services in `app/Services/Backup/`), you MUST run `make backup-test` to verify the complete backup and restore workflow works correctly with real MySQL and PostgreSQL databases.

### Code Quality
```bash
make lint-fix           # Auto-fix code style with Laravel Pint (recommended)
make lint-check         # Check code style without fixing
vendor/bin/pint         # Direct Pint command

make phpstan            # Run PHPStan static analysis
make analyse            # Alias for phpstan
vendor/bin/phpstan analyse --memory-limit=1G  # Direct PHPStan command
```

### Database Operations
```bash
make migrate                # Run pending migrations
make migrate-fresh          # Drop all tables and re-migrate
make migrate-fresh-seed     # Fresh migration with seeders
make db-seed                # Run database seeders
```

### Asset Building
```bash
npm run build           # Build production assets with Vite
npm run dev             # Start Vite dev server for hot module replacement
make build              # Alternative: build via Makefile
```

### Docker (Test Databases)
```bash
docker compose up -d             # Start MySQL and PostgreSQL test containers
docker compose down              # Stop test containers
docker compose down -v           # Stop and remove volumes
```

The Docker setup provides test database servers:
- MySQL 8.0 on port 3306 (user: admin, password: admin, db: testdb)
- PostgreSQL 16 on port 5432 (user: admin, password: admin, db: testdb)

## Architecture

### Application Structure

**Livewire Components**: Class-based components for all interactive pages
- `app/Livewire/DatabaseServer/*` - CRUD operations for database servers with connection testing
- `app/Livewire/DatabaseServer/RestoreModal.php` - 3-step restore wizard (select source, snapshot, destination)
- `app/Livewire/Volume/*` - CRUD operations for storage volumes
- `app/Livewire/Snapshot/Index.php` - List and manage backup snapshots
- `app/Livewire/Settings/*` - User settings pages (Profile, Password, TwoFactor, Appearance, DeleteUserForm)

**Volt Components**: Single-file components for authentication flows (in `resources/views/livewire/`)
- Auth flows: login, register, two-factor, password reset, email verification

**Models**: Uses ULIDs for primary keys
- `DatabaseServer` - Stores connection info (password hidden in responses)
- `Backup` - Backup configuration (recurrence, volume)
- `Snapshot` - Individual backup snapshots with metadata
- `Volume` - Storage destinations (local, S3, etc.)

**Services**:
- `DatabaseConnectionTester` - Tests database connections via PDO with timeout/error handling
- `BackupTask` - Executes database backups (dump, compress, transfer to volume)
- `RestoreTask` - Restores database snapshots (download, decompress, drop/create DB, restore)
- `DatabaseListService` - Lists databases from a server (for autocomplete in restore modal)

### Key Patterns

1. **Livewire Architecture**: The app uses class-based Livewire components for all main pages (CRUD operations, settings) and Volt (single-file components) for authentication flows only.

2. **Mary UI Components**: All UI components use Mary UI (built on daisyUI). Components are used without prefixes (e.g., `<x-button>`, `<x-input>`, `<x-card>`). Key patterns:
   - Modals use `wire:model` with boolean properties (e.g., `$showDeleteModal`)
   - Tables use `<table class="table-default">` with custom styling
   - Alerts use `class="alert-success"` format (not `variant`)
   - Selects use `:options` prop with `[['id' => '', 'name' => '']]` format
   - Dark mode follows system preference (`prefers-color-scheme`)

3. **Database Connection Testing**: The `DatabaseConnectionTester` service builds DSN strings dynamically based on database type (mysql, postgresql, mariadb, sqlite) and provides user-friendly error messages.

4. **Authentication**: Laravel Fortify handles auth with optional two-factor authentication. All main routes require `auth` and `verified` middleware.

5. **Form Validation**: Livewire components use `#[Validate]` attributes or inline validation in methods. Form objects (like `VolumeForm`, `DatabaseServerForm`) encapsulate validation logic.

6. **ULID Primary Keys**: Database models use ULIDs instead of auto-incrementing integers for better distributed system support.

7. **Backup & Restore Workflow**:
   - **Backup**: `BackupTask` uses database-specific dump commands (mysqldump/pg_dump), compresses with gzip, transfers to volume storage
   - **Restore**: `RestoreTask` downloads snapshot, decompresses, validates compatibility, drops/creates target database, restores data
   - **Cross-server restore**: Snapshots can be restored from one server to another (e.g., prod → staging) as long as database types match
   - **Same-server restore**: Can restore old snapshots back to the same server (e.g., rollback)
   - Both services handle MySQL, MariaDB, and PostgreSQL with appropriate SSL/connection handling

### Routing

Routes are defined in `routes/web.php`:
- Public: `/` (welcome page)
- Authenticated: `/dashboard`, `/database-servers/*`, `/volumes/*`, `/snapshots`, `/settings/*`
- Auth routes use `Volt::route()` helper for single-file components
- All other routes use Livewire component classes directly (e.g., `Route::get('database-servers', \App\Livewire\DatabaseServer\Index::class)`)

### Testing Strategy

Tests use Pest PHP with the Laravel plugin:
- Feature tests: `tests/Feature/` - Test complete flows with database interactions
- Unit tests: `tests/Unit/` - Test isolated logic
- Uses in-memory SQLite for testing (configured in phpunit.xml)
- Database servers have dedicated feature tests in `tests/Feature/DatabaseServer/`

## Development Workflow

### Git Hooks (Husky)

Pre-commit hook automatically runs:
1. `make lint-fix` - Auto-format code with Laravel Pint
2. `make test` - Run all Pest tests

Ensure tests pass and code is formatted before committing.

### Running a Single Test

```bash
# Filter by test name or class
make test-filter FILTER=test_can_create_database_server
make test-filter FILTER=DatabaseServerTest
php artisan test --filter=CreateTest
```

### Adding a New Database Type

1. Update validation in Livewire components (`Create.php`, `Edit.php`): Add type to `in:` rule
2. Add DSN builder case in `DatabaseConnectionTester::buildDsn()`
3. Update migration enum if using database enums
4. Add tests for new connection type

### Working with Livewire Components

- Public properties are automatically bound to views
- Use `#[Validate]` attributes or Form objects for validation
- Call `$this->validate()` before processing data
- Use `Session::flash()` for one-time messages (shown via `@if (session('success'))`)
- Return `$this->redirect()` with `navigate: true` for SPA-like navigation
- Blade files contain only view markup; all PHP logic is in component classes

### Working with Mary UI Components

- All components are prefixed with `x-` (e.g., `<x-button>`, `<x-input>`, `<x-card>`)
- Use Heroicons for icons (e.g., `icon="o-user"` for outline icons, `icon="s-user"` for solid)
- Modal pattern: Add boolean property to component class, use `wire:model` in blade
- Select pattern: Use `:options` prop with array format `[['id' => 'value', 'name' => 'Label']]`
- Alert pattern: Use `class="alert-success"`, `class="alert-error"`, etc.
- Form components: `<x-input>`, `<x-password>`, `<x-select>`, `<x-checkbox>`, etc.
- Documentation: https://mary-ui.com/docs/components/button

## Important Files

- `.env.example` - Environment template (copy to `.env`)
- `Makefile` - Convenient development commands
- `.husky/pre-commit` - Git pre-commit hooks
- `phpunit.xml` - Test configuration
- `vite.config.js` - Asset bundling configuration
- `composer.json` - Contains helpful script shortcuts (`composer dev`, `composer test`, `composer setup`)
