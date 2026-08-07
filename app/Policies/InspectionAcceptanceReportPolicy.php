<?php

namespace App\Policies;

use App\Models\InspectionAcceptanceReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InspectionAcceptanceReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inspection_acceptance_reports.view');
    }

    public function view(User $user, InspectionAcceptanceReport $inspectionAcceptanceReport): bool
    {
        return $user->hasPermissionTo('inspection_acceptance_reports.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('inspection_acceptance_reports.create');
    }

    public function update(User $user, InspectionAcceptanceReport $inspectionAcceptanceReport): bool
    {
        return $user->hasPermissionTo('inspection_acceptance_reports.update');
    }

    public function delete(User $user, InspectionAcceptanceReport $inspectionAcceptanceReport): bool
    {
        return $user->hasPermissionTo('inspection_acceptance_reports.delete');
    }
}
