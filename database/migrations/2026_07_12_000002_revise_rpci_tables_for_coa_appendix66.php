<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── rpcis table revisions ─────────────────────────────────
        Schema::table('rpcis', function (Blueprint $table) {
            // ADD new columns
            $table->string('accountable_person')->nullable()->after('fund_cluster');
            $table->string('accountable_position')->nullable()->after('accountable_person');
            $table->date('accountability_date')->nullable()->after('accountable_position');
            $table->text('remarks')->nullable()->after('status');
            $table->string('chairman_name')->nullable()->after('remarks');
            $table->string('member_one_name')->nullable()->after('chairman_name');
            $table->string('member_two_name')->nullable()->after('member_one_name');
            $table->string('approved_by')->nullable()->after('member_two_name');
            $table->string('approved_position')->nullable()->after('approved_by');
            $table->string('verified_by')->nullable()->after('approved_position');
            $table->string('verified_position')->nullable()->after('verified_by');
            $table->string('created_by')->nullable()->after('verified_position');
        });

        // Migrate data from old columns to new columns
        DB::statement('UPDATE rpcis SET accountable_person = accountable_officer');
        DB::statement('UPDATE rpcis SET accountable_position = `position`');
        DB::statement('UPDATE rpcis SET chairman_name = committee_chairman');
        DB::statement('UPDATE rpcis SET member_one_name = committee_member1');
        DB::statement('UPDATE rpcis SET member_two_name = committee_member2');
        DB::statement('UPDATE rpcis SET approved_by = head_of_agency');
        DB::statement('UPDATE rpcis SET approved_position = head_of_agency_designation');
        DB::statement('UPDATE rpcis SET verified_by = coa_representative');
        DB::statement('UPDATE rpcis SET verified_position = coa_representative_designation');
        DB::statement('UPDATE rpcis SET created_by = (SELECT name FROM users WHERE users.id = rpcis.user_id)');

        // DROP old columns
        Schema::table('rpcis', function (Blueprint $table) {
            $table->dropColumn([
                'accountable_officer',
                'position',
                'office',
                'entity_name',
                'rc_code',
                'committee_chairman',
                'committee_chairman_designation',
                'committee_member1',
                'committee_member1_designation',
                'committee_member2',
                'committee_member2_designation',
                'head_of_agency',
                'head_of_agency_designation',
                'coa_representative',
                'coa_representative_designation',
                'prepared_by',
                'prepared_by_designation',
                'noted_by',
                'noted_by_designation',
            ]);
        });

        // ─── rpci_items table revisions ───────────────────────────
        Schema::table('rpci_items', function (Blueprint $table) {
            // ADD new columns
            $table->string('stock_number')->nullable()->after('description');
            $table->string('unit_of_measure')->nullable()->after('stock_number');
            $table->integer('shortage_quantity')->default(0)->after('on_hand_per_count');
            $table->decimal('shortage_value', 15, 2)->default(0)->after('shortage_quantity');
        });

        // Migrate data
        DB::statement('UPDATE rpci_items SET stock_number = stock_no');
        DB::statement('UPDATE rpci_items SET unit_of_measure = `unit`');
        DB::statement('UPDATE rpci_items SET shortage_quantity = shortage_overage_qty');
        DB::statement('UPDATE rpci_items SET shortage_value = shortage_overage_value');

        // DROP old columns
        Schema::table('rpci_items', function (Blueprint $table) {
            $table->dropColumn([
                'stock_no',
                'unit',
                'shortage_overage_qty',
                'shortage_overage_value',
            ]);
        });
    }

    public function down(): void
    {
        // ─── Reverse rpci_items changes ───────────────────────────
        Schema::table('rpci_items', function (Blueprint $table) {
            $table->string('stock_no')->nullable()->after('description');
            $table->string('unit')->nullable()->after('stock_no');
            $table->integer('shortage_overage_qty')->default(0)->after('on_hand_per_count');
            $table->decimal('shortage_overage_value', 15, 2)->default(0)->after('shortage_overage_qty');
        });

        DB::statement('UPDATE rpci_items SET stock_no = stock_number');
        DB::statement('UPDATE rpci_items SET `unit` = unit_of_measure');
        DB::statement('UPDATE rpci_items SET shortage_overage_qty = shortage_quantity');
        DB::statement('UPDATE rpci_items SET shortage_overage_value = shortage_value');

        Schema::table('rpci_items', function (Blueprint $table) {
            $table->dropColumn([
                'stock_number',
                'unit_of_measure',
                'shortage_quantity',
                'shortage_value',
            ]);
        });

        // ─── Reverse rpcis changes ────────────────────────────────
        Schema::table('rpcis', function (Blueprint $table) {
            $table->string('accountable_officer')->nullable()->after('fund_cluster');
            $table->string('position')->nullable()->after('accountable_officer');
            $table->string('office')->default('CHR Region XII')->after('position');
            $table->string('entity_name')->nullable()->default('Commission on Human Rights')->after('status');
            $table->string('rc_code')->nullable()->after('entity_name');
            $table->string('committee_chairman')->nullable()->after('rc_code');
            $table->string('committee_chairman_designation')->nullable()->after('committee_chairman');
            $table->string('committee_member1')->nullable()->after('committee_chairman_designation');
            $table->string('committee_member1_designation')->nullable()->after('committee_member1');
            $table->string('committee_member2')->nullable()->after('committee_member1_designation');
            $table->string('committee_member2_designation')->nullable()->after('committee_member2');
            $table->string('head_of_agency')->nullable()->after('committee_member2_designation');
            $table->string('head_of_agency_designation')->nullable()->after('head_of_agency');
            $table->string('coa_representative')->nullable()->after('head_of_agency_designation');
            $table->string('coa_representative_designation')->nullable()->after('coa_representative');
            $table->string('prepared_by')->nullable()->after('coa_representative_designation');
            $table->string('prepared_by_designation')->nullable()->after('prepared_by');
            $table->string('noted_by')->nullable()->after('prepared_by_designation');
            $table->string('noted_by_designation')->nullable()->after('noted_by');
        });

        DB::statement('UPDATE rpcis SET accountable_officer = accountable_person');
        DB::statement('UPDATE rpcis SET `position` = accountable_position');
        DB::statement('UPDATE rpcis SET committee_chairman = chairman_name');
        DB::statement('UPDATE rpcis SET committee_member1 = member_one_name');
        DB::statement('UPDATE rpcis SET committee_member2 = member_two_name');
        DB::statement('UPDATE rpcis SET head_of_agency = approved_by');
        DB::statement('UPDATE rpcis SET head_of_agency_designation = approved_position');
        DB::statement('UPDATE rpcis SET coa_representative = verified_by');
        DB::statement('UPDATE rpcis SET coa_representative_designation = verified_position');

        Schema::table('rpcis', function (Blueprint $table) {
            $table->dropColumn([
                'accountable_person',
                'accountable_position',
                'accountability_date',
                'remarks',
                'chairman_name',
                'member_one_name',
                'member_two_name',
                'approved_by',
                'approved_position',
                'verified_by',
                'verified_position',
                'created_by',
            ]);
        });
    }
};
