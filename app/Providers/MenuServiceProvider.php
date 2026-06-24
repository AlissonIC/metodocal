<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        view()->composer('*', function ($view) {
            $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
            $verticalMenuData = json_decode($verticalMenuJson);

            $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
            $horizontalMenuData = json_decode($horizontalMenuJson);

            if (isset($verticalMenuData->menu)) {
                $verticalMenuData->menu = $this->filterByPermission($verticalMenuData->menu);
            }
            if (isset($horizontalMenuData->menu)) {
                $horizontalMenuData->menu = $this->filterByPermission($horizontalMenuData->menu);
            }

            $view->with('menuData', [$verticalMenuData, $horizontalMenuData]);
        });
    }

    /**
     * Remove entries the current user lacks permission/role for and collapses
     * empty submenu/header groups.
     */
    private function filterByPermission(array $items): array
    {
        $user = Auth::user();

        $filtered = [];
        foreach ($items as $item) {
            if (isset($item->submenu) && is_array($item->submenu)) {
                $item->submenu = $this->filterByPermission($item->submenu);
                if (count($item->submenu) === 0) {
                    continue;
                }
            }

            if (! $this->isAllowed($item, $user)) {
                continue;
            }

            $filtered[] = $item;
        }

        return $this->dropOrphanHeaders($filtered);
    }

    private function isAllowed(object $item, $user): bool
    {
        if (isset($item->menuHeader)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        // Admin bypass: sempre vê tudo, independente de permission/role
        // (mesma regra do Gate::before em AuthServiceProvider).
        if ($user->hasRole('admin')) {
            return true;
        }

        // role: "admin" ou "admin|mentorado|licenciado"
        if (isset($item->role) && $item->role !== '') {
            $roles = explode('|', $item->role);
            return $user->hasAnyRole($roles);
        }

        // permission: usado pra itens gateados por plano de assinatura.
        // Try/catch protege contra PermissionDoesNotExist quando o DB ainda
        // não tem a permission seedada (ex.: primeiro deploy).
        if (isset($item->permission) && $item->permission !== '') {
            try {
                return $user->hasPermissionTo($item->permission);
            } catch (\Throwable $e) {
                return false;
            }
        }

        return true;
    }

    private function dropOrphanHeaders(array $items): array
    {
        $result = [];
        $count = count($items);
        for ($i = 0; $i < $count; $i++) {
            $current = $items[$i];
            if (isset($current->menuHeader)) {
                $next = $items[$i + 1] ?? null;
                if (! $next || isset($next->menuHeader)) {
                    continue;
                }
            }
            $result[] = $current;
        }
        return $result;
    }
}
