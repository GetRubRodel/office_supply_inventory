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

    /**
     * Official plantilla positions selectable at registration and in
     * User/Profile management.
     */
    public static array $positions = [
        'Administrative Aide I',
        'Administrative Aide II',
        'Administrative Aide III',
        'Administrative Aide IV',
        'Administrative Aide V',
        'Administrative Aide VI',
        'Administrative Officer I',
        'Administrative Officer II',
        'Administrative Officer III',
        'Administrative Officer IV',
        'Administrative Assistant I',
        'Administrative Assistant II',
        'Administrative Assistant III',
        'Security Officer I',
        'Security Officer II',
        'Process Server',
        'Special Investigator I',
        'Special Investigator II',
        'Special Investigator III',
        'Special Investigator IV',
        'Information Officer I',
        'Information Officer II',
        'Training Specialist I',
        'Training Specialist II',
        'Attorney I',
        'Attorney II',
        'Attorney III',
        'Attorney IV',
        'Attorney V',
        'OIC Director',
        'Regional Director',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'division',
        'position',
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

    /**
     * Keep the Spatie role in sync with the legacy `role` column.
     *
     * The RBAC layer is dual: the `role` column drives the role helper
     * methods (canManageProcurement, canIssueItems, etc.), while Filament
     * navigation and policy gates check Spatie permissions via
     * `hasPermissionTo()` (which resolves through Spatie roles).
     *
     * Because roles were only ever assigned by RolePermissionSeeder, any
     * user created or re-roled afterwards (registration, admin create/edit)
     * had an empty Spatie role relation — making every permission check
     * fail and hiding all navigation items. This event re-syncs the Spatie
     * role whenever a user is created or their `role` column changes.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasRecentlyCreated || $user->wasChanged('role')) {
                $role = $user->role;

                if ($role && in_array($role, self::$roles, true)) {
                    $user->syncRoles([$role]);
                }
            }
        });
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

    /**
     * Position Select options (value === label) for registration and
     * User/Profile management forms.
     */
    public static function positionOptions(): array
    {
        return array_combine(self::$positions, self::$positions);
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

    public function isSupplyOfficer(): bool
    {
        return $this->role === self::ROLE_SUPPLY_OFFICER;
    }

    public function isPropertyCustodian(): bool
    {
        return $this->role === self::ROLE_PROPERTY_CUSTODIAN;
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
     * View Purchase Management badge notifications — Admin, Regional
     * Director. These are the approver roles who act on transactions
     * submitted for review (approve/reject).
     */
    public function canViewPurchaseNotifications(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_REGIONAL_DIRECTOR,
        ]);
    }

    /**
     * Submit a Purchase Request for review — only the original requester
     * (the user who created the PR) and the Administrator may submit.
     *
     * All other roles (Supply Officer, Division Chief, Staff, Regional
     * Director, Property Custodian) are excluded unless they are the
     * original requester of that specific PR.
     */
    public function canSubmitPurchaseRequest(?\App\Models\PurchaseRequest $purchaseRequest = null): bool
    {
        // The Administrator may submit any PR for review.
        if ($this->isAdmin()) {
            return true;
        }

        // Only the original requester may submit their own PR for review.
        if ($purchaseRequest) {
            return $purchaseRequest->user_id === $this->id;
        }

        return false;
    }

    /**
     * RIS Approval — Admin (any division), Division Chief (any division).
     * A Division Chief cannot approve their own RIS request; the
     * Administrator retains approval authority over every request.
     */
    public function canApproveRequisition(\App\Models\Requisition $requisition): bool
    {
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }

        if ($this->role === self::ROLE_DIVISION_CHIEF) {
            return $this->id !== $requisition->user_id;
        }

        return false;
    }

    /**
     * Cancel RIS — Admin (any division), Division Chief (any division).
     *
     * Kept separate from canApproveRequisition() so the Division-Chief-
     * requester approval restriction does not also block cancellation of
     * those requests (which would otherwise leave them stranded).
     */
    public function canCancelRequisition(\App\Models\Requisition $requisition): bool
    {
        return $this->role === self::ROLE_ADMIN
            || $this->role === self::ROLE_DIVISION_CHIEF;
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
     * Procurement CRUD (create/edit/delete PR, RFQ, ABC, PO, IAR, BAC) —
     * Admin, Supply Officer, Property Custodian.
     */
    public function canManageProcurement(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_PROPERTY_CUSTODIAN,
        ]);
    }

    /**
     * Inventory CRUD (stock modifications, supply management) —
     * Admin, Supply Officer, Property Custodian.
     */
    public function canManageInventory(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_PROPERTY_CUSTODIAN,
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
     * Determine if the current user may issue a specific requisition.
     * When the requester is a Supply Officer, only the Administrator may
     * perform the Issue action after Division Chief approval.
     */
    public function canIssueRequisition(\App\Models\Requisition $requisition): bool
    {
        if (! $this->hasPermissionTo('requisitions.issue')) {
            return false;
        }

        $requester = $requisition->user;
        if ($requester && $requester->isSupplyOfficer()) {
            return $this->isAdmin();
        }

        return $this->canIssueItems();
    }

    /**
     * Receive RIS items — only the original requester of the RIS who also
     * holds the requisitions.receive permission. This includes Supply
     * Officers receiving their own RIS requests: they issue items for other
     * users, but still acknowledge receipt of the items issued to them.
     */
    public function canReceiveRequisition(\App\Models\Requisition $requisition): bool
    {
        return $requisition->user_id === $this->id
            && $this->hasPermissionTo('requisitions.receive');
    }

    /**
     * Create PR/RIS requests — every authenticated role: Staff, Supply
     * Officer, Division Chief, Regional Director and Property Custodian
     * (the Administrator holds every permission).
     */
    public function canRequestItems(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_STAFF,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_DIVISION_CHIEF,
            self::ROLE_REGIONAL_DIRECTOR,
            self::ROLE_PROPERTY_CUSTODIAN,
        ]);
    }

    /**
     * Access Inventory Management modules (Supplies, Categories, Suppliers, Stock In) —
     * Admin, Supply Officer (CRUD), Division Chief (division).
     */
    public function canAccessInventoryManagement(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_DIVISION_CHIEF,
        ]);
    }

    /**
     * Access Procurement Management modules (RFQ, ABC, PO, IAR, BAC) —
     * Admin, Supply Officer, Regional Director (read-only).
     */
    public function canAccessProcurementManagement(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
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
            self::ROLE_DIVISION_CHIEF,
            self::ROLE_REGIONAL_DIRECTOR,
        ]);
    }

    /**
     * Access master data CRUD (Categories, Suppliers) — Admin, Supply Officer,
     * Property Custodian.
     */
    public function canAccessMasterData(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_PROPERTY_CUSTODIAN,
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
     * Manage legal documents (BAC) — Admin, Supply Officer, Property Custodian.
     */
    public function canManageLegal(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
            self::ROLE_PROPERTY_CUSTODIAN,
        ]);
    }

    /**
     * Physical inventory counting (RPCI) — Admin, Supply Officer, Regional Director.
     */
    public function canPhysicalCount(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
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
     * View all procurement/inventory records — Admin, Supply Officer, Regional Director.
     */
    public function canViewAllRecords(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_SUPPLY_OFFICER,
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
