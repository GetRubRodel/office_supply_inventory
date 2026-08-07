<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Revert 'administrator' -> 'admin'
        User::where('role', 'administrator')->update(['role' => 'admin']);

        // Revert 'supply_officer' -> 'staff'
        User::where('role', 'supply_officer')->update(['role' => 'staff']);

        // Revert any other new roles to 'staff' (as they were not part of the original system)
        User::whereIn('role', [
            'property_custodian',
            'administrative_head',
            'legal_head',
            'promotion_head',
            'investigation_head',
            'regional_director',
        ])->update(['role' => 'staff']);
    }

    public function down(): void
    {
        // Nothing to restore - the RBAC migration has been removed
    }
};
