<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Aristas del árbol del menú (tabla `modules_relations`).
 *
 * En L8 este archivo declaraba `class Modules` (colisión con Modules.php, código muerto).
 * Se corrige el nombre. Se reemplazará por `menu_items` en la consolidación de RBAC
 * (ver PLAN_MIGRACION.md §11).
 */
class ModulesRelation extends Model
{
    use HasFactory;

    protected $table = 'modules_relations';

    protected $fillable = [
        'modules_father_id',
        'modules_son_id',
        'estatus',
    ];
}
