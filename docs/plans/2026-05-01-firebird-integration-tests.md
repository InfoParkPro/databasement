# Firebird Integration Tests Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add real Firebird integration coverage so Firebird connection and backup/restore paths are exercised against a live Firebird service, similar to existing PostgreSQL and MySQL integration tests.

**Architecture:** Extend the existing Docker-based integration environment with a Firebird service and Firebird client utilities in the `app` container. Reuse `tests/Support/IntegrationTestHelpers.php` and the current integration-test style, adding Firebird as another supported database type instead of creating a separate test harness.

**Tech Stack:** Docker Compose, Alpine PHP image, Firebird server/client tools, Laravel Pest integration tests.

---

### Task 1: Add the first failing Firebird connection integration test

**Files:**
- Modify: `tests/Integration/DatabaseConnectionTesterTest.php`

**Step 1: Write the failing test**
- Extend the existing datasets so `connection succeeds`, `connection fails with invalid credentials`, and `connection fails with unreachable host` include `firebird`.

**Step 2: Run test to verify it fails**
- Run: `docker compose -f docker-compose.yml -f docker-compose.local.yml exec --user application -T app php artisan test tests/Integration/DatabaseConnectionTesterTest.php`
- Expected: Firebird case fails because test config / helper / runtime support is missing.

**Step 3: Write minimal implementation**
- No production code yet; only enough infra/config/helper changes to make Firebird connection testing executable.

**Step 4: Run test to verify it passes**
- Re-run the same test file until the Firebird connection cases are green.

### Task 2: Add Firebird integration infra

**Files:**
- Modify: `docker-compose.yml`
- Modify: `docker/php/Dockerfile`
- Modify: `config/testing.php`

**Step 1: Add Firebird service**
- Add a `firebird` service with stable host/port and mounted data directory.

**Step 2: Add Firebird client tools**
- Install `isql-fb` / `gbak` capable packages in the PHP image.

**Step 3: Add test config**
- Add `testing.databases.firebird` env-driven config values.

**Step 4: Verify**
- Rebuild/start containers and confirm the app container can see Firebird client tools.

### Task 3: Extend integration helpers for Firebird

**Files:**
- Modify: `tests/Support/IntegrationTestHelpers.php`

**Step 1: Add Firebird config support**
- Extend `getDatabaseConfig()` with Firebird settings.

**Step 2: Add Firebird DB lifecycle helpers**
- Create helpers to create/load/drop Firebird databases and verify restored data.

**Step 3: Verify**
- Re-run `DatabaseConnectionTesterTest.php` and confirm Firebird helper path works.

### Task 4: Add the first failing Firebird backup/restore integration test

**Files:**
- Modify: `tests/Integration/BackupRestoreTest.php`

**Step 1: Write the failing test**
- Add a Firebird dataset or dedicated test that performs real backup and restore through the existing job flow.

**Step 2: Run test to verify it fails**
- Run: `docker compose -f docker-compose.yml -f docker-compose.local.yml exec --user application -T app php artisan test tests/Integration/BackupRestoreTest.php`
- Expected: Firebird case fails until helper/infra wiring is complete.

**Step 3: Write minimal implementation**
- Add only the missing helper/runtime pieces required for Firebird backup/restore.

**Step 4: Run test to verify it passes**
- Re-run the test file until Firebird backup/restore is green.

### Task 5: Final verification

**Files:**
- No new files expected beyond the above unless fixture scripts are needed.

**Step 1: Run targeted verification**
- Run:
  - `docker compose -f docker-compose.yml -f docker-compose.local.yml exec --user application -T app php artisan test tests/Integration/DatabaseConnectionTesterTest.php`
  - `docker compose -f docker-compose.yml -f docker-compose.local.yml exec --user application -T app php artisan test tests/Integration/BackupRestoreTest.php`

**Step 2: Run formatting / static checks if touched files require it**
- Run:
  - `docker compose -f docker-compose.yml -f docker-compose.local.yml exec --user application -T app vendor/bin/pint --test`
  - `docker compose -f docker-compose.yml -f docker-compose.local.yml exec --user application -T app vendor/bin/phpstan analyse --memory-limit=1G`

**Step 3: Commit**
- Commit once the targeted Firebird integration tests are green and infra changes are stable.
