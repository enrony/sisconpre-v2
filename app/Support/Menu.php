<?php

namespace App\Support;

use App\Models\MenuItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Construye el árbol de navegación visible para un usuario, filtrando cada ítem
 * por su permiso spatie (`menu_items.permission`). Un ítem sin permiso es visible
 * para cualquier autenticado; un grupo sin hijos visibles se descarta.
 */
final class Menu
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function forUser(?Authenticatable $user): array
    {
        if ($user === null) {
            return [];
        }

        $items = MenuItem::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return self::branch($items, null, $user);
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     * @return list<array<string, mixed>>
     */
    private static function branch(Collection $items, ?int $parentId, Authenticatable $user): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->map(function (MenuItem $item) use ($items, $user): ?array {
                if ($item->permission !== null && $user->cannot($item->permission)) {
                    return null;
                }

                $children = self::branch($items, $item->id, $user);

                // grupo (sin ruta propia) sin hijos visibles => se oculta
                if ($children === [] && ($item->route === null || $item->route === '#')) {
                    return null;
                }

                return [
                    'key' => $item->key,
                    'label' => $item->label,
                    'icon' => $item->icon,
                    'route' => $item->route,
                    'children' => $children,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
