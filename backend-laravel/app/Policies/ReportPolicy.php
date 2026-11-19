<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Report $report): bool
    {
        return $user->hasRole('admin')
            || $report->created_by_user_id === $user->id
            || $this->managesSector($user, $report);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Report $report): bool
    {
        return $user->hasRole('admin') || $this->managesSector($user, $report);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Report $report): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Report $report): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return false;
    }

    public function uploadEvidence(User $user, Report $report): bool
    {
        return $this->view($user, $report) && in_array($report->status, ['pending', 'in_progress'], true);
    }

    private function managesSector(User $user, Report $report): bool
    {
        return $user->hasRole('coordinator')
            && $user->managedSectors()->whereKey($report->sector_id)->exists();
    }
}
