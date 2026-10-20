<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the "Committee on Awards" designation columns for existing ABCs.
 *
 * The designation fields in the ABC form are read-only (disabled) and were
 * historically not dehydrated, so designations were never persisted. BAC
 * Resolutions read member designations from the ABC record, so null
 * designations rendered as blank lines. This migration fills the nulls from
 * each member's role so the ABC record is a complete source of truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('abstracts_of_canvass')
            ->whereNull('chairman_designation')
            ->update(['chairman_designation' => 'Chairman']);

        DB::table('abstracts_of_canvass')
            ->whereNull('vice_chairman_designation')
            ->update(['vice_chairman_designation' => 'Vice Chairman']);

        DB::table('abstracts_of_canvass')
            ->whereNull('member1_designation')
            ->update(['member1_designation' => 'Member']);

        DB::table('abstracts_of_canvass')
            ->whereNull('member2_designation')
            ->update(['member2_designation' => 'Member']);

        DB::table('abstracts_of_canvass')
            ->whereNull('member3_designation')
            ->update(['member3_designation' => 'Member']);
    }

    public function down(): void
    {
        // Designations are derived from each member's role; no reverse
        // operation is defined.
    }
};