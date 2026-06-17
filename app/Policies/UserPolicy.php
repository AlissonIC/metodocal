<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('access.users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('access.users.view') || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $user->can('access.users.manage');
    }

    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }
        return $user->can('access.users.manage');
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }
        return $user->can('access.users.manage');
    }
}
