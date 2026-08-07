<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Override Spatie's hasAnyRole to maintain backward compatibility
     * with the existing role-column-based checks.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    // ─── Role Constants ───────────────────────────────────────────

    const ROLE_ADMIN = 'admin';
    const ROLE_STAFF = 'staff';
    const ROLE_REGIONAL_DIRECTOR = 'regional_director';
    const ROLE_DIVISION_CHIEF = 'division_chief';
    const ROLE_SUPPLY_OFFICER = 'supply_officer';
    const ROLE_PROPERTY_CUSTODIAN = 'property_custodian';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_DEACTIVATED = 'deactivated';

    public static array $roles = [
        self::ROLE_ADMIN,
        self::ROLE_STAFF,
        self::ROLE_REGIONAL_DIRECTOR,
        self::ROLE_DIVISION_CHIEF,
        self::ROLE_SUPPLY_OFFICER,
        self::ROLE_PROPERTY_CUSTODIAN,
    ];

    public static array $statuses = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_DEACTIVATED,
    ];

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'division',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Get the display label for a role value. */
    public static function getRoleLabel(string $role): string
    {
        return match ($role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_STAFF => 'Staff',
            self::ROLE_REGIONAL_DIRECTOR => 'Regional Director',
            self::ROLE_DIVISION_CHIEF => 'Division Chief',
            self::ROLE_SUPPLY_OFFICER => 'Supply Officer',
            self::ROLE_PROPERTY_CUSTODIAN => 'Property Custodian',
            default => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    // ─── Role Helpers ─────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isRegionalDirector(): bool
    {
        return $this->role === self::ROLE_REGIONAL_DIRECTOR;
    }

    public function isPropertyCustodian(): bool
    {
        return $this->role === self::ROLE_PROPERTY_CUSTODIAN;
    }

    public function isSupplyOfficer(): bool
    {
        return $this->role === self::ROLE_SUPPLY_OFFICER;
    }

    public function isDivisionChief(): bool
    {
        return $this->role === self::ROLE_DIVISION_CHIEF;
    }

    // ─── RBAC Permission Methods ──────────────────────────────────

    /**
     * User Management & System Settings
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canManageSystemSettings(): bool
    {
        return $this->isAdmin();
    }

    /**
     * PR Approval — Admin, Regional Director (any PR).
     */
    public function canApprove(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_REGIONAL_DIRECTOR,
        ]);
    }

    /**
     * RIS Approval — Admin (any division), Division Chief (any division).
     * Requesters cannot approve their own RIS transactions.
     */
    public function canApproveRequisition(\App\Models\Requisition $requisition): bool
    {
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }

        if ($this->role === self::ROLE_DIVISION_CHIEF) {
            return true;
        }

        return false;
    }

    /**
     * PR Approval — Admin (any division), Regional Director (any PR across all divisions).
     */
    public function canApprovePurchaseRequest(\App\Models\PurchaseRequest $purchaseRequest): bool
    {
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }

        if ($this->role === self::ROLE_REGIONAL_DIRECTOR) {
            return true;
        }

        return false;
    }

    /**
     * Approve procurement documents (RFQ, ABC, PO, IAR, BAC) — Admin, Supply Officer.
     */
    public function canApproveProcurement(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
        ]);
    }

    /**
     * Procurement CRUD (create/edit/delete PR, RFQ, ABC, PO, IAR, BAC) — Admin, Supply Officer.
     */
    public function canManageProcurement(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
        ]);
    }

    /**
     * Inventory CRUD (stock modifications, supply management) — Admin, Supply Officer.
     */
    public function canManageInventory(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
        ]);
    }

    /**
     * Issue items from RIS (deduct stock) — Admin, Supply Officer.
     */
    public function canIssueItems(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
        ]);
    }

    /**
     * Receive RIS items — only the original requester of the RIS.
     * Supply Officer cannot receive (they issue items, not receive them).
     */
    public function canReceiveRequisition(\App\Models\Requisition $requisition): bool
    {
        return $requisition->user_id === $this->id && ! $this->isSupplyOfficer();
    }

    /**
     * Create PR/RIS requests — Staff and Supply Officer.
     * Division Chief is view/approve only; Regional Director and Property Custodian are view-only.
     */
    public function canRequestItems(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_STAFF,
            self::ROLE_SUPPLY_OFFICER,
        ]);
    }

    /**
     * Access Inventory Management modules (Supplies, Categories, Suppliers, Stock In) —
     * Admin, Supply Officer (CRUD), Property Custodian (read-only), Division Chief (division).
     */
    public function canAccessInventoryManagement(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_PROPERTY_CUSTODIAN,
            self::ROLE_DIVISION_CHIEF,
        ]);
    }

    /**
     * Access Procurement Management modules (RFQ, ABC, PO, IAR, BAC) —
     * Admin, Supply Officer (CRUD), Property Custodian (read-only), Regional Director (read-only).
     */
    public function canAccessProcurementManagement(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_PROPERTY_CUSTODIAN,
        ]);
    }

    /**
     * View reports (RSMI, Inventory Reports) — all roles except Staff.
     */
    public function canViewReports(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_PROPERTY_CUSTODIAN,
            self::ROLE_DIVISION_CHIEF,
            self::ROLE_REGIONAL_DIRECTOR,
        ]);
    }

    /**
     * Access master data CRUD (Categories, Suppliers) — Admin, Supply Officer.
     */
    public function canAccessMasterData(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
        ]);
    }

    /**
     * View legal documents (BAC) — Admin, Supply Officer, Regional Director.
     */
    public function canViewLegal(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_REGIONAL_DIRECTOR,
        ]);
    }

    /**
     * Manage legal documents (BAC) — Admin, Supply Officer.
     */
    public function canManageLegal(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
        ]);
    }

    /**
     * Physical inventory counting (RPCI) — Admin, Supply Officer, Property Custodian, Regional Director.
     */
    public function canPhysicalCount(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_PROPERTY_CUSTODIAN,
        ]);
    }

    /**
     * Access the executive dashboard — Admin, Regional Director.
     */
    public function canAccessDashboard(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_REGIONAL_DIRECTOR,
        ]);
    }

    /**
     * View all procurement/inventory records — Admin, Supply Officer, Property Custodian, Regional Director.
     */
    public function canViewAllRecords(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_PROPERTY_CUSTODIAN,
            self::ROLE_REGIONAL_DIRECTOR,
        ]);
    }

    /**
     * View records scoped to own division — Division Chief only.
     */
    public function isDivisionScoped(): bool
    {
        return $this->role === self::ROLE_DIVISION_CHIEF;
    }

    /**
     * View only own records — Staff only.
     */
    public function isOwnRecordsOnly(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    /**
     * Map the user's division (short name from Requisition::DIVISION_MAP)
     * to the corresponding office_division values used in PurchaseRequests.
     *
     * Requisition divisions use short names ('Administrative', 'Promotion', etc.)
     * while PurchaseRequest office_division uses full names ('Administrative Division', etc.).
     */
    public function getDivisionOfficeDivisions(): array
    {
        return match ($this->division) {
            'Administrative' => ['Administrative Division'],
            'Promotion'      => ['Promotion and Advocacy Division'],
            'Investigation'  => ['Investigation Division'],
            'Legal'          => ['Legal Division'],
            default          => [],
        };
    }

    // ─── Status Helpers ───────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isDeactivated(): bool
    {
        return $this->status === self::STATUS_DEACTIVATED;
    }

    public function canLogin(): bool
    {
        return $this->isApproved();
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeDeactivated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DEACTIVATED);
    }

    /**
     * Transition the user to a new status.
     */
    public function setStatus(string $status): bool
    {
        if (! in_array($status, self::$statuses)) {
            return false;
        }

        $this->status = $status;

        return $this->save();
    }
}
