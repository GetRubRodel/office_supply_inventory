<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repair the Requesting Party designation on existing Requisition (RIS) rows.
 *
 * Requisition::creating() previously seeded `requested_by_designation` from
 * the actor's RBAC `role` (e.g. 'staff', 'division_chief'), and the form
 * field was neither auto-populated nor read-only, so the column could also
 * hold a hand-typed or stale value.
 *
 * The designation must be the requester's official plantilla POSITION from
 * their profile (`users.position`). This migration rewrites the column from
 * that source for every RIS whose requester has a position on file, which
 * repairs the print/view/download output for historical records.
 *
 * RBAC role values are explicitly cleared first so no role string can survive
 * as a designation. Rows whose requester has no position (or no requester) are
 * left null rather than being filled with a role, and the application renders
 * those as a blank signature line.
 */
return new class extends Migration
{
    /** RBAC role values that were wrongly stored as designations. */
    private const ROLE_VALUES = [
        'admin',
        'staff',
        'regional_director',
        'division_chief',
        'supply_officer',
        'property_custodian',
    ];

    public function up(): void
    {
        // Drop every stored designation that is not a real position so the
        // join below can replace it from the requester's profile.
        DB::table('requisitions')
            ->whereIn('requested_by_designation', self::ROLE_VALUES)
            ->update(['requested_by_designation' => null]);

        DB::table('requisitions')
            ->whereNotNull('user_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', 'requisitions.user_id')
                    ->whereNotNull('users.position')
                    ->whereRaw("TRIM(users.position) <> ''");
            })
            ->update([
                'requested_by_designation' => DB::raw(
                    '(SELECT TRIM(u.position) FROM users u WHERE u.id = requisitions.user_id)'
                ),
            ]);
    }

    public function down(): void
    {
        // The prior values (RBAC roles) are not meaningful designations and
        // are intentionally not restored.
    }
};
