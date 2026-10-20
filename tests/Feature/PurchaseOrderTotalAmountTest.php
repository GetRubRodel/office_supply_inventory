<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseOrderResource\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrderResource\Pages\EditPurchaseOrder;
use App\Models\BacResolution;
use App\Models\PurchaseOrder;
use App\Models\PoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchaseOrderTotalAmountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = ['dashboard', 'users', 'supplies', 'suppliers', 'categories', 'purchase_requests', 'request_for_quotations', 'abstracts_of_canvass', 'purchase_orders', 'inspection_acceptance_reports', 'bac_resolutions', 'requisitions', 'rpcis', 'rsmi_reports', 'inventory_reports', 'procurement_monitoring_reports', 'audit_logs', 'system_settings', 'stock_in'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'delete', 'approve'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }
        $extras = ['requisitions.issue', 'requisitions.receive', 'purchase_requests.print', 'requisitions.print', 'request_for_quotations.print', 'abstracts_of_canvass.print', 'purchase_orders.print', 'inspection_acceptance_reports.print', 'bac_resolutions.print', 'rpcis.print', 'rpcis.preview', 'inspection_acceptance_reports.stock_in', 'inventory_reports.print', 'rsmi_reports.print'];
        foreach ($extras as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        Role::findOrCreate('admin', 'web')->syncPermissions(Permission::all()->pluck('name')->all());
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'approved',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeBacSource(): BacResolution
    {
        $pr = \App\Models\PurchaseRequest::create([
            'date' => now()->subDays(4),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => \App\Models\PurchaseRequest::STATUS_APPROVED,
        ]);

        $rfq = \App\Models\RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => now()->subDays(3),
        ]);

        $abc = \App\Models\AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
            'supplier1_name' => 'ABC Trading',
            'recommendation' => 'ABC Trading',
            'date_of_opening' => now(),
        ]);

        return BacResolution::create([
            'abc_id' => $abc->id,
            'date' => now(),
        ]);
    }

    public function test_total_amount_is_saved_when_creating_po_via_form(): void
    {
        $admin = $this->makeUser('admin');
        $bac = $this->makeBacSource();

        $this->actingAs($admin);

        $component = Livewire::test(CreatePurchaseOrder::class)
            ->fillForm([
                'bac_resolution_id' => $bac->id,
                'date' => now()->toDateString(),
                'supplier_name' => 'ABC Trading',
                'contact_number' => '09171234567',
                'address' => '123 Rizal St, Koronadal City',
                'tin' => '123456789',
                'items' => [
                    '11111111-1111-1111-1111-111111111111' => [
                        'unit' => 'box',
                        'description' => 'Bond paper',
                        'quantity' => 10,
                        'unit_cost' => 250.00,
                        'amount' => 2500.00,
                    ],
                    '22222222-2222-2222-2222-222222222222' => [
                        'unit' => 'pc',
                        'description' => 'Ballpen',
                        'quantity' => 5,
                        'unit_cost' => 20.00,
                        'amount' => 100.00,
                    ],
                ],
            ]);

        $component
            ->call('create')
            ->assertHasNoFormErrors();

        $po = PurchaseOrder::first();

        $this->assertNotNull($po, 'PO should be created');
        $this->assertSame('2600.00', (string) $po->total_amount, 'total_amount should be 2600.00');
        $this->assertSame('TWO THOUSAND SIX HUNDRED PESOS ONLY', $po->amount_in_words, 'amount_in_words should match the total');
        $this->assertSame(2, $po->items()->count(), 'only the 2 filled items should be saved (empty default rows skipped)');
    }

    public function test_total_amount_is_recomputed_when_editing_po_via_form(): void
    {
        $admin = $this->makeUser('admin');
        $bac = $this->makeBacSource();

        $po = PurchaseOrder::create([
            'bac_resolution_id' => $bac->id,
            'po_no' => 'PO-2026-0001',
            'date' => now()->toDateString(),
            'supplier_name' => 'ABC Trading',
            'contact_number' => '09171234567',
            'address' => '123 Rizal St, Koronadal City',
            'tin' => '123456789',
            'total_amount' => 0.00,
            'amount_in_words' => 'ZERO PESOS ONLY',
        ]);

        PoItem::create([
            'po_id' => $po->id,
            'unit' => 'box',
            'description' => 'Bond paper',
            'quantity' => 10,
            'unit_cost' => 250.00,
            'amount' => 2500.00,
        ]);
        PoItem::create([
            'po_id' => $po->id,
            'unit' => 'pc',
            'description' => 'Ballpen',
            'quantity' => 5,
            'unit_cost' => 20.00,
            'amount' => 100.00,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(EditPurchaseOrder::class, ['record' => $po->getRouteKey()])
            ->fillForm([
                'date' => now()->toDateString(),
                'supplier_name' => 'ABC Trading',
                'contact_number' => '09171234567',
                'address' => '123 Rizal St, Koronadal City',
                'tin' => '123456789',
                'conforme_name' => 'ABC Trading',
                'items' => [
                    'record-1' => [
                        'unit' => 'box',
                        'description' => 'Bond paper',
                        'quantity' => 20,
                        'unit_cost' => 250.00,
                        'amount' => 5000.00,
                    ],
                    'record-2' => [
                        'unit' => 'pc',
                        'description' => 'Ballpen',
                        'quantity' => 5,
                        'unit_cost' => 20.00,
                        'amount' => 100.00,
                    ],
                ],
            ]);

        $component
            ->call('save')
            ->assertHasNoFormErrors();

        $po->refresh();

        $this->assertSame('5100.00', (string) $po->total_amount, 'total_amount should be recomputed to 5100.00');
        $this->assertSame('FIVE THOUSAND ONE HUNDRED PESOS ONLY', $po->amount_in_words, 'amount_in_words should match the new total');
    }
}