<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for the columns that are most frequently filtered
 * (WHERE), sorted (ORDER BY) or joined (JOIN) across the application.
 *
 * Every index is added defensively: the column must exist and the index
 * must not already be present, so the migration is safe to re-run and
 * safe on databases where a foreign key already created a supporting index.
 */
return new class extends Migration
{
    /**
     * table => [ [columns...], ... ]
     *
     * @var array<string, array<int, array<int, string>>>
     */
    private array $indexes = [
        // RIS list scopes, dashboard/report status filters and date ranges.
        'requisitions' => [
            ['status'],
            ['created_at'],
            ['status', 'issued_by_date'],
            ['user_id', 'status'],
        ],

        // Inventory listing: active filter, name sort, category/supplier drill-down.
        'supplies' => [
            ['status'],
            ['name'],
            ['category_id', 'status'],
            ['supplier_id', 'status'],
        ],

        // PR status chart + date filters + own-records scoping.
        'purchase_requests' => [
            ['status'],
            ['created_at'],
            ['user_id', 'status'],
        ],

        // Downstream procurement status badges / eligibility scopes.
        'request_for_quotations' => [['status']],
        'abstracts_of_canvass' => [['status']],
        'bac_resolutions' => [['status']],
        'purchase_orders' => [
            ['status'],
            ['date'],
        ],
        'inspection_acceptance_reports' => [['status']],

        // Stock-in report date ranges and per-supply aggregation.
        'stock_in_records' => [
            ['date_received'],
            ['supply_id', 'date_received'],
        ],

        // Reference / admin filters.
        'categories' => [['status']],
        'users' => [['status']],
        'rpcis' => [
            ['status'],
            ['report_date'],
        ],
        'notifications' => [['read_at']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $definitions) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($definitions as $columns) {
                // Skip if any column is missing (schema drift safety).
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        continue 2;
                    }
                }

                if (Schema::hasIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                    $blueprint->index($columns, $this->indexName($table, $columns));
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $definitions) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($definitions as $columns) {
                $name = $this->indexName($table, $columns);

                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($name) {
                        $blueprint->dropIndex($name);
                    });
                }
            }
        }
    }

    /**
     * Deterministic, MySQL-safe (< 64 chars) index name.
     *
     * @param  array<int, string>  $columns
     */
    private function indexName(string $table, array $columns): string
    {
        return $table . '_' . implode('_', $columns) . '_idx';
    }
};
