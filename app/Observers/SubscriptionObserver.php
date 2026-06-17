<?php

namespace App\Observers;

use App\Models\Subscription;

class SubscriptionObserver
{
    public function created(Subscription $subscription): void
    {
        $this->sync($subscription);
    }

    public function updated(Subscription $subscription): void
    {
        if ($subscription->wasChanged('status') || $subscription->wasChanged('plan_id')) {
            $this->sync($subscription);
        }
    }

    public function deleted(Subscription $subscription): void
    {
        $this->sync($subscription, forceRevoke: true);
    }

    /**
     * Sync permissions on the user based on their current active subscriptions
     * + the base permissions of their role(s).
     */
    private function sync(Subscription $subscription, bool $forceRevoke = false): void
    {
        $user = $subscription->user()->first();
        if (! $user) {
            return;
        }

        // Base: all permissions granted to the user's roles.
        $rolePermissions = $user->getPermissionsViaRoles()->pluck('name')->all();

        // Plus: permissions from every currently-active subscription.
        $planPermissions = [];
        if (! $forceRevoke) {
            $active = $user->subscriptions()->where('status', 'ativa')->with('plan:id,permissions')->get();
            foreach ($active as $sub) {
                $perms = $sub->plan?->permissions ?? [];
                foreach ($perms as $p) {
                    $planPermissions[$p] = true;
                }
            }
        }

        $final = array_unique(array_merge($rolePermissions, array_keys($planPermissions)));
        $user->syncPermissions($final);
    }
}
