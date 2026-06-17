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

        if (isset($item->permission) && $item->permission !== '') {
            // Verifica permissão DIRETA (sem passar pelo Gate::before do admin).
            // Itens de cliente só aparecem para quem tem a permission via role/plano.
            return $user->hasPermissionTo($item->permission);
        }

        if (isset($item->role) && $item->role !== '') {
            return $user->hasRole($item->role);
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
