<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clientes extends Model
{
    use HasFactory;

    public $fillable = [
        'documento',
        'nombre',
        'nombre_segundo',
        'apellido',
        'apellido_segundo',
        'telefono',
        'direccion',
        'grupos_trabajos_user_id',
        'idtipo_documento',
        'email',
        'city_id',
        'email_verified_at',
    ];

    protected $with = ['tipo_documento'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'full_document',
        'prestamos_activos',
        'country_id',
        'informes_pendientes',
    ];

    /**
     * Usuario con el que el cliente entra al sistema (ve solo su cartera, ver
     * `AlcanceCartera`). No es asignable masivamente a propósito.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Vincula esta ficha con el usuario de mismo email, si la ficha no tiene
     * usuario, el email identifica a una sola ficha y ese usuario no tiene otra.
     */
    public function vincularUsuarioPorEmail(): void
    {
        $email = mb_strtolower(trim((string) $this->email));

        if ($this->user_id || $email === '' || static::whereRaw('lower(email) = ?', [$email])->count() !== 1) {
            return;
        }

        $userId = User::whereRaw('lower(email) = ?', [$email])->value('id');

        if ($userId && ! static::where('user_id', $userId)->exists()) {
            $this->forceFill(['user_id' => $userId])->save();
        }
    }

    /** Al registrarse un usuario: si una única ficha sin usuario tiene su email, se vinculan. */
    public static function vincularConUsuario(User $user): void
    {
        $fichas = static::whereNull('user_id')
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($user->email))])
            ->get();

        if ($fichas->count() === 1 && ! static::where('user_id', $user->id)->exists()) {
            $fichas->first()?->vincularUsuarioPorEmail();
        }
    }

    public function tipo_documento()
    {
        return $this->belongsTo(tiposDocumentos::class, 'idtipo_documento');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function Pretamos()
    {
        return $this->hasMany(Prestamos::class, 'cliente_id')->whereHas('prestamos_dias');
    }

    public function InformesPandientesActivos()
    {
        return $this->hasMany(PaymentReport::class, 'cliente_id')->where('approved', false)->whereHas('payment_reports_movement', function ($query) {
            $query->latest();
        });
    }

    public function getFullNameAttribute()
    {
        return $this->nombre.' '.$this->nombre_segundo.' '.$this->apellido.' '.$this->apellido_segundo;
    }

    public function getFullDocumentAttribute()
    {
        return $this->tipo_documento->sigla.'-'.$this->documento;
    }

    public function getCountryIdAttribute()
    {
        return $this->city->country_id;
    }

    public function getPrestamosActivosAttribute()
    {

        if (count($this->Pretamos) > 0) {
            return $this->Pretamos->filter(function ($prestamo) {
                return ! $prestamo->pagado && ! $prestamo->anulado && ! $prestamo->perdido;
            })->count();
        }

        return 0;

    }

    public function getInformesPendientesAttribute()
    {

        if (count($this->InformesPandientesActivos) > 0) {
            return $this->InformesPandientesActivos->filter(function ($InformesPandiente) {

                if (in_array($InformesPandiente->payment_reports_movement[0]->estatus, [1, 4])) {
                    return $InformesPandiente;
                }

            })->count();
        }

        return 0;

    }
}
