<?php

namespace App\Policies;

use App\Enums\DebtStatus;
use App\Models\DebtRecord;
use App\Models\User;

class DebtRecordPolicy
{
    /**
     * Determine whether the user can view a list of debt records
     * Mahasiswa can view own transactions
     * Admin can view all transactions
     */
    public function viewAny(User $user): bool
    {
        // Both admin and mahasiswa can view (list akan di-filter berdasarkan role)
        return true;
    }

    /**
     * Determine whether the user can view the debt record
     * Creator, counterpart, and admin can view
     */
    public function view(User $user, DebtRecord $debtRecord): bool
    {
        // Admin can view any debt record
        if ($user->isAdmin()) {
            return true;
        }

        // Creator can view
        if ($debtRecord->creator_id === $user->id) {
            return true;
        }

        // Counterpart can view
        if ($debtRecord->counterpart_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create a debt record
     * Only mahasiswa can create
     */
    public function create(User $user): bool
    {
        return $user->isMahasiswa();
    }

    /**
     * Determine whether the user can update the debt record
     * Only creator can update, and only if status is pending and not settled
     */
    public function update(User $user, DebtRecord $debtRecord): bool
    {
        // Admin can always update (for management)
        if ($user->isAdmin()) {
            return true;
        }

        // Only creator can update
        if ($debtRecord->creator_id !== $user->id) {
            return false;
        }

        // Cannot edit if settled or rejected
        if ($debtRecord->status === DebtStatus::SETTLED || $debtRecord->status === DebtStatus::REJECTED) {
            return false;
        }

        // Only pending status can be edited
        return $debtRecord->status === DebtStatus::PENDING;
    }

    /**
     * Determine whether the user can delete the debt record
     * Only creator can delete, and only if status is pending
     */
    public function delete(User $user, DebtRecord $debtRecord): bool
    {
        // Admin can always delete
        if ($user->isAdmin()) {
            return true;
        }

        // Only creator can delete
        if ($debtRecord->creator_id !== $user->id) {
            return false;
        }

        // Can only delete if pending
        return $debtRecord->status === DebtStatus::PENDING;
    }

    /**
     * Determine whether the user can confirm the debt record
     * Only counterpart can confirm
     */
    public function confirm(User $user, DebtRecord $debtRecord): bool
    {
        // Admin can confirm (for management)
        if ($user->isAdmin()) {
            return true;
        }

        // Only counterpart can confirm
        if ($debtRecord->counterpart_id !== $user->id) {
            return false;
        }

        // Can only confirm if pending
        return $debtRecord->status === DebtStatus::PENDING;
    }

    /**
     * Determine whether the user can reject the debt record
     * Only counterpart can reject
     */
    public function reject(User $user, DebtRecord $debtRecord): bool
    {
        // Admin can reject (for management)
        if ($user->isAdmin()) {
            return true;
        }

        // Only counterpart can reject
        if ($debtRecord->counterpart_id !== $user->id) {
            return false;
        }

        // Can only reject if pending
        return $debtRecord->status === DebtStatus::PENDING;
    }

    /**
     * Determine whether the user can settle the debt record
     * Creator or counterpart can settle
     */
    public function settle(User $user, DebtRecord $debtRecord): bool
    {
        // Admin can settle (for management)
        if ($user->isAdmin()) {
            return true;
        }

        // Creator or counterpart can settle
        if ($debtRecord->creator_id !== $user->id && $debtRecord->counterpart_id !== $user->id) {
            return false;
        }

        // Can only settle if active
        return $debtRecord->status === DebtStatus::ACTIVE;
    }

    /**
     * Determine whether the user can view active transactions
     */
    public function viewActive(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view history
     */
    public function viewHistory(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can search debt records
     */
    public function search(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can export debt records
     * Admin can export, mahasiswa can export own
     */
    public function export(User $user): bool
    {
        return true;  // Both can export (filtered by own)
    }

    /**
     * Determine whether the user can view statistics
     * Admin can view all statistics
     * Mahasiswa can view own statistics
     */
    public function viewStats(User $user): bool
    {
        return true;
    }
}
