<?php

namespace App\Policies;

use App\Models\User;

class StudentPolicy
{
    /**
     * Determine whether the user can view a list of students
     * Only admin can view student list
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the student
     * Only admin can view student details
     */
    public function view(User $user, User $student): bool
    {
        // Admin can view any student
        if ($user->isAdmin()) {
            return true;
        }

        // Mahasiswa can only view their own profile
        return $user->id === $student->id;
    }

    /**
     * Determine whether the user can create a student
     * Only admin can create students
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the student
     * Only admin can update students
     */
    public function update(User $user, User $student): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the student
     * Only admin can delete students
     */
    public function delete(User $user, User $student): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the student
     * Only admin can restore students
     */
    public function restore(User $user, User $student): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the student
     * Only admin can force delete students
     */
    public function forceDelete(User $user, User $student): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can search students
     * Mahasiswa bisa search semua mahasiswa (untuk membuat debt record)
     * Admin bisa search semua
     */
    public function search(User $user): bool
    {
        return true;  // Both admin dan mahasiswa dapat search
    }

    /**
     * Determine whether the user can export students
     * Only admin can export
     */
    public function export(User $user): bool
    {
        return $user->isAdmin();
    }
}
