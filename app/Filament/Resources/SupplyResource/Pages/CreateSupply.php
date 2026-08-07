<?php

namespace App\Filament\Resources\SupplyResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\SupplyResource;
use App\Models\StockInRecord;
use App\Models\Supply;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateSupply extends CreateRecord
{
    protected static string $resource = SupplyResource::class;

    public function mount(): void
    {
        parent::mount();

        if (! CurrentUser::get()?->canManageInventory()) {
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Supply
    {
        return DB::transaction(function () use ($data) {
            $stockNo = $data['stock_no'] ?? null;
            $itemName = trim($data['name'] ?? '');

            // Priority 1: If stock_no is provided and already exists, treat as stock-in
            if (!empty($stockNo)) {
                $existingSupply = Supply::where('stock_no', $stockNo)->first();
                if ($existingSupply) {
                    // Update the existing supply with the stock-in entry
                    $quantity = (int) ($data['current_stock'] ?? 0);
                    $unitCost = (float) ($data['unit_cost'] ?? $data['unit_price'] ?? 0);

                    if ($quantity > 0) {
                        $existingSupply->receiveStock($quantity, $unitCost);
                    }

                    // Update other fields if provided
                    $fillableFields = array_filter([
                        'category_id' => $data['category_id'] ?? null,
                        'supplier_id' => $data['supplier_id'] ?? null,
                        'name' => $data['name'] ?? null,
                        'description' => $data['description'] ?? null,
                        'unit' => $data['unit'] ?? null,
                        'reorder_level' => $data['reorder_level'] ?? null,
                        'unit_price' => $data['unit_price'] ?? null,
                        'status' => $data['status'] ?? 'active',
                    ], fn ($value) => $value !== null);

                    $existingSupply->update($fillableFields);

                    // Record the stock-in transaction for audit trail
                    if ($quantity > 0) {
                        $stockIn = new StockInRecord();
                        $stockIn->reference_number = StockInRecord::generateReferenceNumber();
                        $stockIn->supply_id = $existingSupply->id;
                        $stockIn->quantity_received = $quantity;
                        $stockIn->unit_cost = $unitCost;
                        $stockIn->supplier = $existingSupply->supplier?->name ?? $data['supplier'] ?? null;
                        $stockIn->date_received = now()->toDateString();
                        $stockIn->remarks = 'Stock-in via Supply Management (existing stock_no: ' . $stockNo . ')';
                        $stockIn->save();
                    }

                    return $existingSupply;
                }
            }

            // Priority 2: Check for duplicate by item description (name)
            if ($itemName !== '') {
                $existingByName = Supply::whereRaw('LOWER(name) = ?', [mb_strtolower($itemName)])->first();
                if ($existingByName) {
                    $quantity = (int) ($data['current_stock'] ?? 0);
                    $unitCost = (float) ($data['unit_cost'] ?? $data['unit_price'] ?? 0);

                    if ($quantity > 0) {
                        $existingByName->receiveStock($quantity, $unitCost);
                    }

                    $fillableFields = array_filter([
                        'category_id' => $data['category_id'] ?? null,
                        'supplier_id' => $data['supplier_id'] ?? null,
                        'description' => $data['description'] ?? null,
                        'unit' => $data['unit'] ?? null,
                        'reorder_level' => $data['reorder_level'] ?? null,
                        'unit_price' => $data['unit_price'] ?? null,
                        'status' => $data['status'] ?? 'active',
                    ], fn ($value) => $value !== null);

                    $existingByName->update($fillableFields);

                    if ($quantity > 0) {
                        $stockIn = new StockInRecord();
                        $stockIn->reference_number = StockInRecord::generateReferenceNumber();
                        $stockIn->supply_id = $existingByName->id;
                        $stockIn->quantity_received = $quantity;
                        $stockIn->unit_cost = $unitCost;
                        $stockIn->supplier = $existingByName->supplier?->name ?? $data['supplier'] ?? null;
                        $stockIn->date_received = now()->toDateString();
                        $stockIn->remarks = 'Stock-in via Supply Management (existing item: ' . $existingByName->stock_no . ')';
                        $stockIn->save();
                    }

                    return $existingByName;
                }
            }

            // Create new supply item
            $supply = new Supply();
            $supply->stock_no = $stockNo ?? Supply::generateStockNo();
            $supply->category_id = $data['category_id'];
            $supply->supplier_id = $data['supplier_id'] ?? null;
            $supply->name = $data['name'];
            $supply->description = $data['description'] ?? null;
            $supply->unit = $data['unit'] ?? null;
            $supply->reorder_level = (int) ($data['reorder_level'] ?? 0);
            $supply->current_stock = (int) ($data['current_stock'] ?? 0);
            $supply->unit_price = (float) ($data['unit_price'] ?? 0);
            $supply->unit_cost = (float) ($data['current_stock'] ?? 0) > 0
                ? (float) ($data['unit_price'] ?? 0)
                : 0;
            $supply->average_unit_cost = $supply->unit_cost; // kept in sync for DB backward compatibility
            $supply->status = $data['status'] ?? 'active';
            $supply->reference_number = $data['reference_number'] ?? null;
            $supply->save();

            // Record stock-in for new supply if quantity > 0
            if ($supply->current_stock > 0) {
                $stockIn = new StockInRecord();
                $stockIn->reference_number = StockInRecord::generateReferenceNumber();
                $stockIn->supply_id = $supply->id;
                $stockIn->quantity_received = $supply->current_stock;
                $stockIn->unit_cost = $supply->unit_cost;
                $stockIn->supplier = $supply->supplier?->name ?? null;
                $stockIn->date_received = now()->toDateString();
                $stockIn->remarks = 'Initial stock entry via Supply Management';
                $stockIn->save();
            }

            return $supply;
        });
    }
}
