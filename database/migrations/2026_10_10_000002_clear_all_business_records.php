<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clear every transactional / business record in the system.
     *
     * Preserved (never touched):
     *  - categories, users
     *  - RBAC: roles, permissions, model_has_roles, model_has_permissions,
     *    role_has_permissions
     *  - framework/system: migrations, sessions, cache, cache_locks, jobs,
     *    job_batches, failed_jobs, password_reset_otps, password_reset_tokens
     *
     * Foreign-key checks are disabled while truncating so the order of the
     * tables does not matter. TRUNCATE also resets each table's
     * AUTO_INCREMENT counter back to 1.
     */
    private array $tables = [
        'abc_items',
        'abstracts_of_canvass',
        'bac_resolutions',
        'iar_items',
        'inspection_acceptance_reports',
        'notifications',
        'po_items',
        'purchase_orders',
        'purchase_request_items',
        'purchase_requests',
        'request_for_quotations',
        'requisition_items',
        'requisitions',
        'rfq_items',
        'rpci_items',
        'rpcis',
        'stock_in_records',
        'suppliers',
        'supplies',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($this->tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // One-way data cleanup: the cleared records cannot be restored.
    }
};
