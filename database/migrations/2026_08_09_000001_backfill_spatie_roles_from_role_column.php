<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill Spatie roles for existing users based on their `role` column.
 *
 * Historically, Spatie roles were only assigned by RolePermissionSeeder at
 * seed time. Users created or re-roled afterwards (self-registration, admin
 * create/edit) had an empty Spatie role relation, which made every
 * `hasPermissionTo()` check fail and hid all Filament navigation items.
 *
 * This migration aligns the Spatie roles table with the `role` column so
 * that all existing users inherit the permissions of their assigned role.
 */
return new class extends Migration
{
    private const ROLE_MAP = [
        'admin'              => 'admin',
        'supply_officer'     => 'supply_officer',
        'property_custodian' => 'property_custodian',
        'staff'              => 'staff',
        'division_chief'     => 'division_chief',
        'regional_director'  => 'regional_director',
    ];

    public function up(): void
    {
        User::query()
            ->whereIn('role', array_keys(self::ROLE_MAP))
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $role = self::ROLE_MAP[$user->role] ?? null;

                    if ($role) {
                        $user->syncRoles([$role]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Role assignments are derived state from the `role` column;
        // no reverse operation is defined.
    }
};
