<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Relaciones cargadas siempre (portado del sistema legado).
     *
     * @var list<string>
     */
    protected $with = ['profiles', 'ownedUserCountry'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Asignaciones de grupo de trabajo del usuario (multi-tenencia por grupo).
     *
     * @return HasMany<GruposTrabajoUser, $this>
     */
    public function ownedGruposTrabajoUser(): HasMany
    {
        return $this->hasMany(GruposTrabajoUser::class, 'iduser');
    }

    /**
     * @return HasMany<ProfilesUsers, $this>
     */
    public function ownedProfilesUser(): HasMany
    {
        return $this->hasMany(ProfilesUsers::class, 'users_id');
    }

    /**
     * @return HasMany<UserCountry, $this>
     */
    public function ownedUserCountry(): HasMany
    {
        return $this->hasMany(UserCountry::class);
    }

    /**
     * Perfiles (RBAC propio, se consolidará en spatie — ver PLAN_MIGRACION.md §11).
     *
     * @return HasMany<ProfilesUsers, $this>
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(ProfilesUsers::class, 'users_id', 'id');
    }
}
