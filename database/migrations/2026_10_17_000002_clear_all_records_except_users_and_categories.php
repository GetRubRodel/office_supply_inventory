<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clear every record in the system EXCEPT users and categories.
     *
     * Preserved row-for-row:
     *  - users, categories
     *  - RBAC: roles, permissions, role_has_permissions,
     *    model_has_roles, model_has_permissions
     *  - migrations (migration bookkeeping)
     *
     * Every other table is truncated, including the framework runtime tables
     * (cache, cache_locks, sessions, jobs, job_batches, failed_jobs,
     * password_reset_tokens, password_reset_otps). TRUNCATE also resets each
     * table's AUTO_INCREMENT counter back to 1, so the next inserted record
     * receives id 1.
     *
     * Table structures, columns, indexes and foreign-key constraints are left
     * untouched: foreign-key checks are only disabled for the duration of the
     * truncation so the order of the tables does not matter.
     *
     * This migration is one-way. A full database backup must be taken before
     * running it (see storage/backups).
     */
    private array $preserve = [
        'users',
        'categories',
        'roles',
        'permissions',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'migrations',
    ];

    public function up(): void
    {
        $connection = DB::connection();

        // Scope the listing to the current database. Passing null would list
        // every schema the MySQL user can see (other databases, phpMyAdmin
        // tables, ...). SQLite only has the "main" schema, so null is correct
        // there and keeps the migration portable for the test suite.
        $schema = $connection->getDriverName() === 'sqlite'
            ? null
            : $connection->getDatabaseName();

        $tables = array_values(array_filter(
            Schema::getTableListing($schema, false),
            fn (string $table): bool => ! in_array($table, $this->preserve, true)
                && ! str_starts_with($table, 'sqlite_')
        ));

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // One-way data cleanup: the cleared records cannot be restored.
        // Restore from the pre-clear backup in storage/backups if needed.
    }
};
