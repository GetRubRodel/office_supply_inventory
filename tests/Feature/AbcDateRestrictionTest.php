<?php

namespace Tests\Feature;

use App\Models\AbstractOfCanvass;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Date of Advertisement and Date of Opening must fall on today or a future
 * date. Any previous date is rejected at the model level, so direct
 * requests/API submissions are blocked just like the Filament form.
 */
class AbcDateRestrictionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build the PR -> RFQ -> ABC chain. The ABC is created with today's date
     * for both fields (always valid), then returned for further assertions.
     */
    private function makeAbc(array $dates = []): AbstractOfCanvass
    {
        $pr = PurchaseRequest::create([
            'date' => now()->subDays(4),
            'office_division' => 'Administrative Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_APPROVED,
        ]);

        $rfq = RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => now()->subDays(3),
        ]);

        return AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
            'supplier1_name' => 'Test Supplier Co.',
            'supplier2_name' => 'Other Supplier Inc.',
            'supplier3_name' => 'Third Supplier LLC',
            'recommendation' => 'Test Supplier Co.',
            'date_of_advertisement' => $dates['date_of_advertisement'] ?? now(),
            'date_of_opening' => $dates['date_of_opening'] ?? now(),
            'status' => 'draft',
        ]);
    }

    public function test_create_rejects_yesterday_for_advertisement(): void
    {
        $this->expectException(ValidationException::class);

        $this->makeAbc(['date_of_advertisement' => now()->subDay()]);
    }

    public function test_create_rejects_yesterday_for_opening(): void
    {
        $this->expectException(ValidationException::class);

        $this->makeAbc(['date_of_opening' => now()->subDay()]);
    }

    public function test_create_rejects_previous_month_dates(): void
    {
        $this->expectException(ValidationException::class);

        $this->makeAbc([
            'date_of_advertisement' => now()->startOfMonth()->subDay(),
            'date_of_opening' => now()->startOfMonth()->subDay(),
        ]);
    }

    public function test_create_accepts_today(): void
    {
        $abc = $this->makeAbc();

        $this->assertNotNull($abc->id);
        $this->assertTrue($abc->date_of_advertisement->isToday());
        $this->assertTrue($abc->date_of_opening->isToday());
    }

    public function test_create_accepts_future_dates(): void
    {
        $abc = $this->makeAbc([
            'date_of_advertisement' => now()->addDay(),
            'date_of_opening' => now()->addWeek(),
        ]);

        $this->assertNotNull($abc->id);
        $this->assertTrue($abc->date_of_advertisement->isFuture());
        $this->assertTrue($abc->date_of_opening->isFuture());
    }

    public function test_update_rejects_changing_a_date_to_yesterday(): void
    {
        $abc = $this->makeAbc();

        $this->expectException(ValidationException::class);

        $abc->update(['date_of_opening' => now()->subDay()]);
    }

    public function test_update_of_non_date_fields_is_allowed(): void
    {
        $abc = $this->makeAbc();

        $abc->update(['recommendation' => 'Other Supplier Inc.']);

        $this->assertSame('Other Supplier Inc.', $abc->fresh()->recommendation);
    }
}