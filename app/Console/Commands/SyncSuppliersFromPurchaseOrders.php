<?php

namespace App\Console\Commands;

use App\Services\SupplierSyncService;
use Illuminate\Console\Command;

class SyncSuppliersFromPurchaseOrders extends Command
{
    protected $signature = 'suppliers:sync-from-pos';

    protected $description = 'Mirror supplier information from Purchase Orders into the Suppliers module (creates missing suppliers, links POs, fills blank fields only). Idempotent.';

    public function handle(SupplierSyncService $service): int
    {
        $count = $service->backfillAll();

        $this->info("Synced {$count} purchase order(s) into the Suppliers module.");

        return self::SUCCESS;
    }
}
