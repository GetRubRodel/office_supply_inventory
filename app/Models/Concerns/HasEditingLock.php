<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasEditingLock
{
    /**
     * The duration in minutes after which a lock is considered stale.
     */
    public static function getLockTimeoutMinutes(): int
    {
        return (int) config('purchase-management.lock_timeout_minutes', 5);
    }

    /**
     * The user who is currently editing this record.
     */
    public function editingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editing_by_user_id');
    }

    /**
     * Try to acquire the editing lock for the given user.
     *
     * Returns true if the lock was acquired (or was already held by this user).
     * Returns false if the record is locked by another user and the lock is still valid.
     */
    public function acquireEditingLock(User $user): bool
    {
        // Already locked by this user — refresh the timestamp
        if ($this->editing_by_user_id && (int) $this->editing_by_user_id === (int) $user->id) {
            $this->editing_started_at = now();
            $this->saveQuietly();

            return true;
        }

        // Not locked — acquire
        if ($this->editing_by_user_id === null) {
            $this->editing_by_user_id = $user->id;
            $this->editing_started_at = now();
            $this->saveQuietly();

            return true;
        }

        // Locked by someone else — check staleness
        if ($this->isStale()) {
            // Stale lock — take over
            $this->editing_by_user_id = $user->id;
            $this->editing_started_at = now();
            $this->saveQuietly();

            return true;
        }

        // Locked by someone else and lock is still fresh
        return false;
    }

    /**
     * Release the editing lock.
     */
    public function releaseEditingLock(): void
    {
        $this->editing_by_user_id = null;
        $this->editing_started_at = null;
        $this->saveQuietly();
    }

    /**
     * Force-release the editing lock regardless of who holds it.
     */
    public function forceReleaseEditingLock(): void
    {
        $this->releaseEditingLock();
    }

    /**
     * Check if the current lock (if any) is stale.
     */
    public function isStale(): bool
    {
        if ($this->editing_started_at === null) {
            return false;
        }

        return $this->editing_started_at->diffInMinutes(now()) > static::getLockTimeoutMinutes();
    }

    /**
     * Get the display name of who is editing this record, respecting staleness.
     */
    public function getEditingDisplayNameAttribute(): ?string
    {
        if ($this->editing_by_user_id === null) {
            return null;
        }

        if ($this->isStale()) {
            return null;
        }

        return $this->editingUser?->name;
    }
}
