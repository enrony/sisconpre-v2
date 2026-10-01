<?php

namespace App\Services;

use App\Models\Clientes;
use App\Models\GruposTrabajoUser;
use App\Models\PaymentReport;
use App\Models\Prestamos;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Qué parte de la cartera (clientes, préstamos, cuotas, informes de pago) ve
 * el usuario autenticado. Única regla para todas las pantallas:
 *
 * - **todo**: super-usuario.
 * - **grupos**: personal con `cartera.ver-grupo` → lo registrado por cualquier
 *   miembro de los grupos de trabajo a los que pertenece.
 * - **cliente**: usuario vinculado a una ficha de cliente (`clientes.user_id`)
 *   → solo lo suyo.
 * - **nada**: cualquier otro usuario.
 *
 * Se suma al filtro por país activo que ya aplica cada pantalla.
 */
class AlcanceCartera
{
    public const TODO = 'todo';

    public const GRUPOS = 'grupos';

    public const CLIENTE = 'cliente';

    public const NADA = 'nada';

    private ?string $tipo = null;

    /** @var list<int>|null */
    private ?array $gtuIds = null;

    private ?int $clienteId = null;

    private bool $clienteResuelto = false;

    /**
     * Usuario para el que vale lo memorizado. El contenedor puede reutilizar
     * esta instancia entre requests (p. ej. el controlador que la recibe queda
     * cacheado en la ruta): si cambió el usuario, se recalcula todo.
     */
    private int|string|null $memoriaDe = null;

    public function tipo(): string
    {
        $this->sincronizarUsuario();

        return $this->tipo ??= $this->resolverTipo();
    }

    private function sincronizarUsuario(): void
    {
        $actual = auth()->id();

        if ($actual !== $this->memoriaDe) {
            $this->memoriaDe = $actual;
            $this->tipo = null;
            $this->gtuIds = null;
            $this->clienteId = null;
            $this->clienteResuelto = false;
        }
    }

    public function esCliente(): bool
    {
        return $this->tipo() === self::CLIENTE;
    }

    /** Ficha de cliente vinculada al usuario, si la tiene. */
    public function clienteId(): ?int
    {
        $this->sincronizarUsuario();

        if (! $this->clienteResuelto) {
            $userId = $this->usuario()?->id;
            $id = $userId ? Clientes::where('user_id', $userId)->value('id') : null;
            $this->clienteId = $id === null ? null : (int) $id;
            $this->clienteResuelto = true;
        }

        return $this->clienteId;
    }

    /**
     * Miembros (`grupos_trabajos_users.id`) de los grupos a los que pertenece el
     * usuario: lo que registró cualquiera de ellos es cartera del grupo.
     *
     * @return list<int>
     */
    public function gtuIds(): array
    {
        $this->sincronizarUsuario();

        if ($this->gtuIds === null) {
            $grupos = GruposTrabajoUser::where('iduser', $this->usuario()?->id)
                ->where('estatus', 1)
                ->pluck('idgrupo_trabajo');

            $this->gtuIds = array_values(GruposTrabajoUser::whereIn('idgrupo_trabajo', $grupos)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all());
        }

        return $this->gtuIds;
    }

    /**
     * @template TBuilder of EloquentBuilder<*>|QueryBuilder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function prestamos(EloquentBuilder|QueryBuilder $query, string $tabla = 'prestamos'): EloquentBuilder|QueryBuilder
    {
        match ($this->tipo()) {
            self::TODO => null,
            self::GRUPOS => $query->whereIn("{$tabla}.grupos_trabajos_user_id", $this->gtuIds()),
            self::CLIENTE => $query->where("{$tabla}.cliente_id", $this->clienteId()),
            default => $query->whereRaw('1 = 0'),
        };

        return $query;
    }

    /**
     * @template TBuilder of EloquentBuilder<*>|QueryBuilder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function informes(EloquentBuilder|QueryBuilder $query, string $tabla = 'payment_reports'): EloquentBuilder|QueryBuilder
    {
        match ($this->tipo()) {
            self::TODO => null,
            self::GRUPOS => $query->whereIn("{$tabla}.grupos_trabajos_user_id", $this->gtuIds()),
            self::CLIENTE => $query->where("{$tabla}.cliente_id", $this->clienteId()),
            default => $query->whereRaw('1 = 0'),
        };

        return $query;
    }

    /**
     * Clientes del grupo: los que registró un miembro, o los que tienen algún
     * préstamo registrado por un miembro.
     *
     * @template TBuilder of EloquentBuilder<*>|QueryBuilder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function clientes(EloquentBuilder|QueryBuilder $query, string $tabla = 'clientes'): EloquentBuilder|QueryBuilder
    {
        match ($this->tipo()) {
            self::TODO => null,
            self::GRUPOS => $query->where(fn ($q) => $q
                ->whereIn("{$tabla}.grupos_trabajos_user_id", $this->gtuIds())
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')->from('prestamos')
                    ->whereColumn('prestamos.cliente_id', "{$tabla}.id")
                    ->whereIn('prestamos.grupos_trabajos_user_id', $this->gtuIds()))),
            self::CLIENTE => $query->where("{$tabla}.id", $this->clienteId()),
            default => $query->whereRaw('1 = 0'),
        };

        return $query;
    }

    public function puedeVerCliente(int $clienteId): bool
    {
        return $this->tipo() === self::TODO
            || $this->clientes(Clientes::query()->whereKey($clienteId))->exists();
    }

    public function puedeVerPrestamo(Prestamos $prestamo): bool
    {
        return $this->tipo() === self::TODO
            || $this->prestamos(Prestamos::query()->whereKey($prestamo->id))->exists();
    }

    public function puedeVerInforme(PaymentReport $informe): bool
    {
        return $this->tipo() === self::TODO
            || $this->informes(PaymentReport::query()->whereKey($informe->id))->exists();
    }

    public function exigirCliente(int $clienteId): void
    {
        abort_unless($this->puedeVerCliente($clienteId), 403, 'Este cliente no pertenece a su cartera.');
    }

    public function exigirPrestamo(Prestamos $prestamo): void
    {
        abort_unless($this->puedeVerPrestamo($prestamo), 403, 'Este préstamo no pertenece a su cartera.');
    }

    public function exigirInforme(PaymentReport $informe): void
    {
        abort_unless($this->puedeVerInforme($informe), 403, 'Este informe de pago no pertenece a su cartera.');
    }

    private function resolverTipo(): string
    {
        $usuario = $this->usuario();

        return match (true) {
            $usuario === null => self::NADA,
            // Mismo criterio que Controller::isSuperUsuario(), sin volver a buscar el usuario.
            $usuario->hasRole('super-admin') => self::TODO,
            $usuario->can('cartera.ver-grupo') => self::GRUPOS,
            $this->clienteId() !== null => self::CLIENTE,
            default => self::NADA,
        };
    }

    private function usuario(): ?User
    {
        $usuario = auth()->user();

        return $usuario instanceof User ? $usuario : null;
    }
}
