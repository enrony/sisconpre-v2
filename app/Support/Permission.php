<?php

namespace App\Support;

/**
 * Catálogo de habilidades de permiso.
 *
 * Los permisos de spatie se nombran `"<modulo>.<habilidad>"`, donde `<modulo>`
 * es la `clave` del módulo (tabla `modules` del legado / `menu_items.key`) y
 * `<habilidad>` uno de los slugs de abajo (portados de la tabla `actions`).
 *
 * `acceso-total` NO se materializa como permiso: al sincronizar concede todos
 * los permisos del módulo al rol (ver `rbac:sync-from-legacy`).
 */
final class Permission
{
    public const ACCESO_TOTAL = 'acceso-total';

    public const REGISTRAR = 'registrar';

    public const EDITAR = 'editar';

    public const DUPLICAR = 'duplicar';

    public const ELIMINAR = 'eliminar';

    public const IMPRIMIR = 'imprimir';

    public const EXPORTAR = 'exportar';

    public const IMPORTAR = 'importar';

    public const ANULAR = 'anular';

    public const ELIMINAR_MASIVO = 'eliminar-masivo';

    public const LISTAR = 'listar';

    public const GESTIONAR_INFORME_DE_PAGO = 'gestionar-informe-de-pago';

    /** `actions.code` (legado) → slug de habilidad. */
    public const BY_CODE = [
        '01' => self::ACCESO_TOTAL,
        '02' => self::REGISTRAR,
        '03' => self::EDITAR,
        '04' => self::DUPLICAR,
        '05' => self::ELIMINAR,
        '06' => self::IMPRIMIR,
        '07' => self::EXPORTAR,
        '08' => self::IMPORTAR,
        '09' => self::ANULAR,
        '10' => self::ELIMINAR_MASIVO,
        '11' => self::LISTAR,
        '12' => self::GESTIONAR_INFORME_DE_PAGO,
    ];

    /**
     * Construye el nombre de permiso `"<modulo>.<habilidad>"`.
     */
    public static function name(string $module, string $ability): string
    {
        return "{$module}.{$ability}";
    }
}
