<?php

namespace Tests\Feature;

use App\Filament\Resources\BacResolutionResource;
use App\Filament\Resources\BacResolutionResource\Pages\CreateBacResolution;
use App\Filament\Resources\BacResolutionResource\Pages\ViewBacResolution;
use App\Models\AbcItem;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use App\Support\BacResolutionText;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The BAC Resolution body follows the fixed sample layout: four WHEREAS
 * clauses, the "NOW, THEREFORE ... hereby resolves to:" clause and resolution
 * items 1, 2 and 3. The former "FURTHER RESOLVED" section is gone.
 *
 * The body carries exactly five blanks:
 *
 *   Blank 1 — Division        Select, chosen by the authorized user
 *   Blank 2 — Division        read-only, retrieved from Blank 1
 *   Blank 3 — Budget Amount   TextInput, entered by the authorized user
 *   Blank 4 — Division        read-only, retrieved from Blank 1
 *   Blank 5 — Budget Amount   read-only, retrieved from Blank 3
 *
 * Only blanks 1 and 3 are persisted. The other three are always derived, so
 * the form, view, print and download outputs can never disagree and no
 * Division or Budget Amount is ever hardcoded into the document.
 *
 * The closing statement additionally carries the date/time picked on the
 * resolution DateTimePicker, rendered as a second paragraph:
 *
 *   SO RESOLVED.
 *
 *   Done this <selected date>, City of Koronadal, Philippines.
 */
class BacResolutionBlanksTest extends TestCase
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

        Role::findOrCreate('admin', 'web')->syncPermissions([
            'abstracts_of_canvass.view',
            'bac_resolutions.view',
            'bac_resolutions.create',
            'bac_resolutions.update',
            'bac_resolutions.delete',
        ]);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'approved',
        ]);
        $user->assignRole('admin');

        return $user;
    }

    private function makeAbc(): AbstractOfCanvass
    {
        $pr = PurchaseRequest::create([
            'date' => now()->subDays(4),
            'office_division' => 'Administrative Support Division',
            'name_of_project' => 'Supply of Office Materials',
            'purpose' => 'Replenish office materials',
            'source_of_fund' => 'GAA',
            'status' => PurchaseRequest::STATUS_APPROVED,
        ]);

        $rfq = RequestForQuotation::create([
            'purchase_request_id' => $pr->id,
            'date' => now()->subDays(3),
        ]);

        $abc = AbstractOfCanvass::create([
            'rfq_id' => $rfq->id,
            'supplier1_name' => 'Test Supplier Co.',
            'supplier2_name' => 'Other Supplier Inc.',
            'supplier3_name' => 'Third Supplier LLC',
            'date_of_advertisement' => now(),
            'date_of_opening' => now(),
            'status' => 'draft',
        ]);

        AbcItem::create([
            'abc_id' => $abc->id,
            'item_number' => 1,
            'description' => 'Bond Paper',
            'unit' => 'Ream',
            'quantity' => 10,
            'supplier1_price' => 100.00,
            'supplier2_price' => 80.00,
            'supplier3_price' => 90.00,
        ]);

        return $abc->fresh();
    }

    private function makeBacFrom(
        AbstractOfCanvass $abc,
        string $division = 'Legal Division',
        string $budget = 'PHP 50,000.00',
    ): BacResolution {
        return BacResolution::create([
            'abc_id' => $abc->id,
            'division' => $division,
            'budget_amount' => $budget,
            'date' => now(),
        ]);
    }

    private function makeBac(
        string $division = 'Legal Division',
        string $budget = 'PHP 50,000.00',
    ): BacResolution {
        return $this->makeBacFrom($this->makeAbc(), $division, $budget);
    }

    /**
     * The serialised Livewire payload echoes the raw form state, which
     * includes the stored (tokenised) template columns. Strip those internal
     * attributes so assertions only ever inspect what the user actually sees.
     */
    private function visibleHtml(string $html): string
    {
        return preg_replace('/\swire:(snapshot|effects|initial-data|ignore)="[^"]*"/', '', $html) ?? $html;
    }

    // ── Divisions offered by blank 1 ────────────────────────────────────────

    public function test_blank_one_offers_exactly_the_four_divisions(): void
    {
        $this->assertSame([
            'Administrative Support Division',
            'Promotion and Advocacy Division',
            'Investigation Division',
            'Legal Division',
        ], BacResolution::DIVISIONS);

        $this->actingAs($this->makeAdmin());
        $abc = $this->makeAbc();

        Livewire::test(CreateBacResolution::class)
            ->set('data.abc_id', $abc->id)
            ->assertFormFieldExists('division', fn (Select $field): bool => $field->getOptions() === BacResolutionResource::divisionOptions()
                && $field->isRequired()
                && ! $field->isDisabled());
    }

    // ── Blank wiring ────────────────────────────────────────────────────────

    public function test_blank_two_and_four_retrieve_the_first_blank(): void
    {
        $this->actingAs($this->makeAdmin());
        $abc = $this->makeAbc();

        // Blanks 2 and 4 both retrieve the Division picked in blank 1.
        Livewire::test(CreateBacResolution::class)
            ->set('data.abc_id', $abc->id)
            ->set('data.division', 'Investigation Division')
            ->assertSet('data.division_blank_2', 'Investigation Division')
            ->assertSet('data.division_blank_4', 'Investigation Division');
    }

    public function test_blank_five_retrieves_the_third_blank(): void
    {
        $this->actingAs($this->makeAdmin());
        $abc = $this->makeAbc();

        Livewire::test(CreateBacResolution::class)
            ->set('data.abc_id', $abc->id)
            ->set('data.budget_amount', 'PHP 12,345.67')
            ->assertSet('data.budget_blank_5', 'PHP 12,345.67');
    }

    public function test_blank_three_is_an_editable_text_input(): void
    {
        $this->actingAs($this->makeAdmin());
        $abc = $this->makeAbc();

        Livewire::test(CreateBacResolution::class)
            ->set('data.abc_id', $abc->id)
            ->assertFormFieldExists('budget_amount', fn (TextInput $field): bool => $field->isRequired()
                && ! $field->isReadOnly()
                && ! $field->isDisabled());
    }

    public function test_retrieved_blanks_are_read_only_and_never_persisted(): void
    {
        $this->actingAs($this->makeAdmin());
        $abc = $this->makeAbc();

        $isReadOnlyMirror = fn (TextInput $field): bool => $field->isReadOnly()
            && ! $field->isDisabled()
            && ! $field->isRequired()
            && ! $field->isDehydrated();

        Livewire::test(CreateBacResolution::class)
            ->set('data.abc_id', $abc->id)
            ->assertFormFieldExists('division_blank_2', $isReadOnlyMirror)
            ->assertFormFieldExists('division_blank_4', $isReadOnlyMirror)
            ->assertFormFieldExists('budget_blank_5', $isReadOnlyMirror);
    }

    public function test_only_blank_one_and_three_are_persisted(): void
    {
        $bac = $this->makeBac('Legal Division', 'PHP 50,000.00');

        $row = $bac->fresh()->getAttributes();

        // Blank 1 holds the Division the user selected ...
        $this->assertSame('Legal Division', $row['division']);
        // ... and Blank 3 the amount the user typed.
        $this->assertSame('PHP 50,000.00', $row['budget_amount']);

        // The derived blanks are never stored.
        foreach (['division_blank_2', 'division_blank_4', 'budget_blank_5'] as $derived) {
            $this->assertArrayNotHasKey($derived, $row);
        }
    }

    // ── Rendering ───────────────────────────────────────────────────────────

    public function test_division_is_substituted_into_every_division_blank(): void
    {
        $bac = $this->makeBac('Administrative Support Division', 'PHP 50,000.00');

        $text = implode("\n", array_column($bac->getResolutionBlocks(), 'text'));

        // Blanks 1, 2 and 4 are all Division blanks.
        $this->assertSame(3, substr_count($text, 'Administrative Support Division'));
        $this->assertSame(2, substr_count($text, 'PHP 50,000.00'));

        // No token may survive into the rendered document.
        $this->assertStringNotContainsString(BacResolutionText::DIVISION_TOKEN, $text);
        $this->assertStringNotContainsString(BacResolutionText::BUDGET_TOKEN, $text);
    }

    public function test_rendered_blocks_follow_the_sample_structure(): void
    {
        $bac = $this->makeBac('Administrative Support Division');
        $blocks = $bac->getResolutionBlocks();
        $types = array_column($blocks, 'type');

        $this->assertSame('title', $types[0]);

        $this->assertSame(4, count(array_filter($types, fn ($t) => $t === 'whereas')));
        $this->assertSame(3, count(array_filter($types, fn ($t) => $t === 'item')));
        $this->assertSame('closing', end($types));

        $text = implode("\n", array_column($blocks, 'text'));

        foreach ($blocks as $block) {
            if ($block['type'] === 'whereas') {
                $this->assertStringStartsWith('WHEREAS,', $block['text']);
            }
        }

        $this->assertStringContainsString(
            'NOW, THEREFORE, on motion duly seconded and approved, the Bids and Awards Committee (BAC) hereby resolves to:',
            $text,
        );
        // The item carries the Division selected in blank 1.
        $this->assertStringContainsString('1. AUTHORIZE the procurement of office supplies for the Administrative Support Division', $text);
        $this->assertStringContainsString('2. ENSURE that the total amount to be procured shall not exceed the approved budget of PHP 50,000.00', $text);
        $this->assertStringContainsString('3. REQUIRE that all disbursements shall be subject to existing budgeting', $text);
    }

    public function test_further_resolved_is_removed_from_the_document(): void
    {
        $bac = $this->makeBac();

        $text = implode("\n", array_column($bac->getResolutionBlocks(), 'text'));

        $this->assertStringNotContainsString('FURTHER RESOLVED', $text);
        $this->assertStringNotContainsString('Further Resolved', $text);
    }

    public function test_further_resolved_is_never_written_again(): void
    {
        $bac = $this->makeBac();

        // The clause is obsolete: it is not mass-assignable, and the model's
        // save path does not touch it, so the column can never be repopulated.
        $this->assertNotContains('further_resolved', $bac->getFillable());

        // A legacy value therefore cannot survive a save.
        $bac->update(['further_resolved' => 'FURTHER RESOLVED, to delegate the fuel procurement.']);

        $this->assertNull($bac->fresh()->further_resolved);
    }

    public function test_stored_body_is_the_tokenised_template_not_the_entered_values(): void
    {
        $bac = $this->makeBac('Legal Division', 'PHP 50,000.00');

        $stored = $bac->fresh()->only(['preamble', 'operative_part', 'closing']);

        $this->assertStringContainsString(BacResolutionText::DIVISION_TOKEN, $stored['preamble']);
        $this->assertStringContainsString(BacResolutionText::BUDGET_TOKEN, $stored['preamble']);
        $this->assertStringContainsString(BacResolutionText::DIVISION_TOKEN, $stored['operative_part']);
        $this->assertStringContainsString(BacResolutionText::BUDGET_TOKEN, $stored['operative_part']);

        // The concrete values must never be baked into the stored template.
        $this->assertStringNotContainsString('Legal Division', $stored['preamble']);
        $this->assertStringNotContainsString('PHP 50,000.00', $stored['preamble']);
        $this->assertStringNotContainsString('Legal Division', $stored['operative_part']);
        $this->assertStringNotContainsString('PHP 50,000.00', $stored['operative_part']);
    }

    // ── Output parity ───────────────────────────────────────────────────────

    public function test_resolution_title_is_sentence_case(): void
    {
        $bac = $this->makeBac('Legal Division', 'PHP 50,000.00');

        $title = $bac->getResolutionBlocks()[0]['text'];

        $this->assertSame(
            'A resolution authorizing the procurement of office supplies for Legal Division '
            .'through small value procurement as provided in the Implementing Rules and '
            .'Regulations (IRR) of Republic Act No. 12009.',
            $title,
        );

        // Not all caps.
        $this->assertStringNotContainsString('RESOLUTION AUTHORIZING', $title);
        $this->assertStringNotContainsString('SMALL VALUE', $title);

        // The proper nouns stay capitalised.
        $this->assertStringContainsString('Implementing Rules and Regulations (IRR)', $title);
        $this->assertStringContainsString('Republic Act No. 12009', $title);
    }

    public function test_print_title_is_not_bold(): void
    {
        $admin = $this->makeAdmin();
        $bac = $this->makeBac('Legal Division', 'PHP 50,000.00');

        $html = $this->actingAs($admin)
            ->get(route('bac.print', $bac))
            ->assertOk()
            ->getContent();

        // The title rule must render at regular weight.
        preg_match('/\.body-text \.title-block\s*\{[^}]*\}/', $html, $rule);
        $this->assertNotEmpty($rule, 'The .title-block rule is missing from the print view.');
        $this->assertStringContainsString('font-weight: normal', $rule[0]);
        $this->assertStringNotContainsString('font-weight: bold', $rule[0]);

        // All caps must not be forced back on by the stylesheet.
        $this->assertStringNotContainsString('text-transform: uppercase', $rule[0]);

        // The title paragraph itself must carry no bold override, and the
        // Division substituted into its blank must inherit that same weight.
        preg_match('#<p class="title-block">(.*?)</p>#s', $html, $paragraph);
        $this->assertNotEmpty($paragraph, 'The resolution title paragraph is missing from the print view.');
        $this->assertSame(
            'A resolution authorizing the procurement of office supplies for Legal Division '
            .'through small value procurement as provided in the Implementing Rules and '
            .'Regulations (IRR) of Republic Act No. 12009.',
            trim(html_entity_decode(strip_tags($paragraph[1]), ENT_QUOTES)),
        );
        $this->assertStringNotContainsString('<strong', $paragraph[1]);
        $this->assertStringNotContainsString('<b>', $paragraph[1]);
        $this->assertStringNotContainsString('font-weight', $paragraph[1]);
        $this->assertStringNotContainsString('uppercase', $paragraph[1]);
        $this->assertStringContainsString('Legal Division', $paragraph[1]);
    }

    public function test_print_so_resolved_is_not_bold(): void
    {
        $admin = $this->makeAdmin();
        $bac = $this->makeBac();

        $html = $this->actingAs($admin)
            ->get(route('bac.print', $bac))
            ->assertOk()
            ->getContent();

        preg_match('/\.body-text \.closing\s*\{[^}]*\}/', $html, $rule);
        $this->assertNotEmpty($rule, 'The .closing rule is missing from the print view.');
        $this->assertStringContainsString('font-weight: normal', $rule[0]);
        $this->assertStringNotContainsString('font-weight: bold', $rule[0]);

        // The closing is two paragraphs separated by one blank line, which HTML
        // would collapse without an explicit whitespace rule.
        $this->assertStringContainsString('white-space: pre-line', $rule[0]);

        preg_match('#<p class="closing">(.*?)</p>#s', $html, $paragraph);
        $this->assertNotEmpty($paragraph, 'The "SO RESOLVED." paragraph is missing from the print view.');

        $this->assertSame(
            "SO RESOLVED.\n\nDone this ".$bac->getResolvedOn().', '.BacResolutionText::CLOSING_PLACE.'.',
            trim(strip_tags($paragraph[1])),
        );
        $this->assertStringNotContainsString('<strong', $paragraph[1]);
        $this->assertStringNotContainsString('<b>', $paragraph[1]);
        $this->assertStringNotContainsString('font-weight', $paragraph[1]);
    }

    public function test_print_title_is_followed_by_one_blank_line_before_the_whereas_clauses(): void
    {
        $admin = $this->makeAdmin();
        $bac = $this->makeBac();

        $html = $this->actingAs($admin)
            ->get(route('bac.print', $bac))
            ->assertOk()
            ->getContent();

        // Exactly one spacer line sits between the title and the first WHEREAS.
        $this->assertSame(1, substr_count($html, '<p class="title-spacer"'));

        $spacerPosition = strpos($html, '<p class="title-spacer"');
        $titlePosition = strpos($html, '<p class="title-block">');
        $whereasPosition = strpos($html, '<p class="whereas">');

        $this->assertNotFalse($titlePosition);
        $this->assertNotFalse($spacerPosition);
        $this->assertNotFalse($whereasPosition);
        $this->assertGreaterThan($titlePosition, $spacerPosition);
        $this->assertGreaterThan($spacerPosition, $whereasPosition);
    }

    public function test_form_and_print_show_identical_values(): void
    {
        $admin = $this->makeAdmin();
        $bac = $this->makeBac('Investigation Division', 'PHP 77,777.00');

        $formHtml = $this->visibleHtml(
            $this->actingAs($admin)
                ->get(BacResolutionResource::getUrl('view', ['record' => $bac]))
                ->assertOk()
                ->getContent(),
        );

        $printHtml = $this->visibleHtml(
            $this->actingAs($admin)
                ->get(route('bac.print', $bac))
                ->assertOk()
                ->getContent(),
        );

        foreach ([$formHtml, $printHtml] as $html) {
            // Both blanks show the retrieved values ...
            $this->assertStringContainsString('Investigation Division', $html);
            $this->assertStringContainsString('PHP 77,777.00', $html);

            // ... the removed section is gone ...
            $this->assertStringNotContainsString('FURTHER RESOLVED', $html);

            // ... and no unsubstituted token leaks into the output.
            $this->assertStringNotContainsString(BacResolutionText::DIVISION_TOKEN, $html);
            $this->assertStringNotContainsString(BacResolutionText::BUDGET_TOKEN, $html);
        }

        // The printed document and the form preview render the very same
        // resolved wording, block for block.
        foreach (array_column($bac->getResolutionBlocks(), 'text') as $blockText) {
            $this->assertStringContainsString($blockText, $formHtml);
            $this->assertStringContainsString($blockText, $printHtml);
        }
    }

    public function test_form_preview_updates_live_with_the_selected_division_and_budget(): void
    {
        $this->actingAs($this->makeAdmin());
        $abc = $this->makeAbc();

        $component = Livewire::test(CreateBacResolution::class)
            ->set('data.abc_id', $abc->id)
            ->set('data.division', 'Promotion and Advocacy Division')
            ->set('data.budget_amount', 'PHP 15,000.00');

        // The live preview resolves all five blanks from the two inputs.
        foreach (BacResolutionText::blocks('Promotion and Advocacy Division', 'PHP 15,000.00') as $block) {
            $component->assertSee($block['text'], escape: false);
        }

        $this->assertStringNotContainsString(
            BacResolutionText::DIVISION_TOKEN,
            $this->visibleHtml($component->html()),
        );
        $this->assertStringNotContainsString(
            BacResolutionText::BUDGET_TOKEN,
            $this->visibleHtml($component->html()),
        );
    }

    // ── Resolved date in the closing statement ──────────────────────────────

    public function test_date_field_is_a_required_datetimepicker(): void
    {
        $this->actingAs($this->makeAdmin());
        $abc = $this->makeAbc();

        Livewire::test(CreateBacResolution::class)
            ->set('data.abc_id', $abc->id)
            ->assertFormFieldExists('date', fn (DatePicker $field): bool => $field->isRequired()
                && ! $field->isDisabled()
                // No date in the past may be selected. Filament stores the
                // bound as a string, so it is parsed before comparing.
                && Carbon::parse((string) $field->getMinDate())->isSameDay(now()->startOfDay()));
    }

    public function test_closing_statement_carries_the_selected_date(): void
    {
        $bac = $this->makeBac();

        $closing = collect($bac->getResolutionBlocks())
            ->firstWhere('type', 'closing')['text'];

        $this->assertSame(
            "SO RESOLVED.\n\nDone this ".$bac->getResolvedOn().', '.BacResolutionText::CLOSING_PLACE.'.',
            $closing,
        );

        // Exactly one blank line separates the two paragraphs.
        $this->assertSame(1, substr_count($closing, "\n\n"));
        $this->assertStringStartsWith('SO RESOLVED.', $closing);
        $this->assertStringContainsString('Done this ', $closing);
        $this->assertStringContainsString(', City of Koronadal, Philippines.', $closing);
        $this->assertStringNotContainsString(BacResolutionText::RESOLVED_DATE_TOKEN, $closing);
    }

    public function test_selected_date_is_persisted_with_its_time(): void
    {
        $picked = now()->setTime(14, 30);

        $bac = BacResolution::create([
            'abc_id' => $this->makeAbc()->id,
            'division' => 'Legal Division',
            'budget_amount' => 'PHP 50,000.00',
            'date' => $picked,
        ]);

        $stored = $bac->fresh()->date;

        $this->assertInstanceOf(Carbon::class, $stored);
        $this->assertSame('00:00:00', $stored->format('H:i:s'));
        $this->assertSame($picked->toDateString(), $stored->toDateString());
    }

    public function test_print_and_view_both_show_the_resolved_date(): void
    {
        $admin = $this->makeAdmin();
        $bac = $this->makeBac();
        $expected = 'Done this '.$bac->getResolvedOn().', City of Koronadal, Philippines.';

        $printHtml = $this->actingAs($admin)
            ->get(route('bac.print', $bac))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($expected, $printHtml);

        // The old standalone dateline block is gone — the date now lives in
        // the closing statement only.
        $this->assertStringNotContainsString('class="dateline"', $printHtml);

        Livewire::actingAs($admin)
            ->test(ViewBacResolution::class, ['record' => $bac->getRouteKey()])
            ->assertOk()
            ->assertSee($expected);
    }

    public function test_closing_falls_back_to_a_blank_when_no_date_is_chosen(): void
    {
        $this->assertSame(
            BacResolutionText::BLANK_PLACEHOLDER,
            BacResolutionText::resolvedOnLabel(null),
        );
        $this->assertSame(
            BacResolutionText::BLANK_PLACEHOLDER,
            BacResolutionText::resolvedOnLabel(''),
        );
        $this->assertSame(
            BacResolutionText::BLANK_PLACEHOLDER,
            BacResolutionText::resolvedOnLabel('not a real date'),
        );

        $bac = new BacResolution;

        $this->assertSame(BacResolutionText::BLANK_PLACEHOLDER, $bac->getResolvedOn());
    }

    public function test_form_preview_updates_live_with_the_picked_date(): void
    {
        $this->actingAs($this->makeAdmin());
        $abc = $this->makeAbc();

        $picked = now()->startOfDay()->setTime(9, 15);

        Livewire::test(CreateBacResolution::class)
            ->set('data.abc_id', $abc->id)
            ->set('data.division', 'Legal Division')
            ->set('data.budget_amount', 'PHP 15,000.00')
            ->set('data.date', $picked->format('Y-m-d H:i:s'))
            ->assertSee(
                'Done this '.$picked->format(BacResolutionText::RESOLVED_DATE_FORMAT).', City of Koronadal, Philippines.',
                escape: false,
            );
    }

    public function test_changing_the_date_updates_the_closing(): void
    {
        $bac = $this->makeBac();

        $before = $bac->getResolvedOn();

        $bac->update(['date' => now()->addDay()->setTime(8, 0)]);

        $after = $bac->fresh()->getResolutionBlocks();
        $after = collect($after)->firstWhere('type', 'closing')['text'];

        $this->assertStringNotContainsString($before, $after);
        $this->assertStringContainsString(
            'Done this '.now()->addDay()->format(BacResolutionText::RESOLVED_DATE_FORMAT).', City of Koronadal, Philippines.',
            $after,
        );
    }

    // ── Validation ──────────────────────────────────────────────────────────

    public function test_an_unlisted_division_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        BacResolution::create([
            'abc_id' => $this->makeAbc()->id,
            'division' => 'Finance Division',
            'budget_amount' => 'PHP 1,000.00',
            'date' => now(),
        ]);
    }

    public function test_an_unlisted_division_is_rejected_on_update(): void
    {
        $bac = $this->makeBac();

        $this->expectException(ValidationException::class);

        $bac->update(['division' => 'Finance Division']);
    }

    public function test_changing_the_division_repopulates_the_derived_blanks(): void
    {
        $bac = $this->makeBac('Legal Division', 'PHP 50,000.00');

        $bac->update(['division' => 'Investigation Division']);

        $text = implode("\n", array_column($bac->fresh()->getResolutionBlocks(), 'text'));

        $this->assertSame(3, substr_count($text, 'Investigation Division'));
        $this->assertStringNotContainsString('Legal Division', $text);
    }

    public function test_changing_the_budget_repopulates_the_derived_blank(): void
    {
        $bac = $this->makeBac('Legal Division', 'PHP 50,000.00');

        $bac->update(['budget_amount' => 'PHP 60,000.00']);

        $text = implode("\n", array_column($bac->fresh()->getResolutionBlocks(), 'text'));

        $this->assertSame(2, substr_count($text, 'PHP 60,000.00'));
        $this->assertStringNotContainsString('PHP 50,000.00', $text);
    }

    public function test_unfilled_blanks_render_as_blank_lines_not_tokens(): void
    {
        $bac = BacResolution::create([
            'abc_id' => $this->makeAbc()->id,
            'date' => now(),
        ]);

        $text = implode("\n", array_column($bac->fresh()->getResolutionBlocks(), 'text'));

        $this->assertStringNotContainsString(BacResolutionText::DIVISION_TOKEN, $text);
        $this->assertStringNotContainsString(BacResolutionText::BUDGET_TOKEN, $text);
        $this->assertSame(5, substr_count($text, BacResolutionText::BLANK_PLACEHOLDER));
    }

    // ── Preserved functionality ─────────────────────────────────────────────

    public function test_abc_source_dependency_is_still_enforced(): void
    {
        $this->expectException(ValidationException::class);

        BacResolution::create([
            'division' => 'Legal Division',
            'budget_amount' => 'PHP 50,000.00',
            'date' => now(),
        ]);
    }

    public function test_an_abc_cannot_be_converted_twice(): void
    {
        $abc = $this->makeAbc();

        $this->makeBacFrom($abc);

        $this->expectException(ValidationException::class);

        $this->makeBacFrom($abc);
    }

    public function test_document_numbering_is_preserved(): void
    {
        $first = $this->makeBac();
        $second = $this->makeBac();

        $this->assertSame('BAC RESO-'.now()->year.'-0001', $first->resolution_no);
        $this->assertSame('BAC RESO-'.now()->year.'-0002', $second->resolution_no);
    }

    public function test_view_page_is_reachable_and_shows_the_retrieved_values(): void
    {
        $admin = $this->makeAdmin();
        $bac = $this->makeBac('Administrative Support Division', 'PHP 25,000.00');

        Livewire::actingAs($admin)
            ->test(ViewBacResolution::class, ['record' => $bac->getRouteKey()])
            ->assertOk()
            ->assertSee('Administrative Support Division')
            ->assertSee('PHP 25,000.00')
            ->assertDontSee('FURTHER RESOLVED');
    }
}
