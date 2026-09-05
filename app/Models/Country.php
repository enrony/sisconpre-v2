<?php

namespace App\Models;

use App\Http\Traits\generalsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use generalsTrait, HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id', 'Name', 'Continent', 'Region', 'SurfaceArea', 'IndepYear', 'Population', 'LifeExpectancy', 'GNP', 'GNPOld', 'LocalName', 'GovernmentForm', 'HeadOfState', 'Capital',
        'Code2', 'estatus', 'timezone',
    ];

    public function scopeWhereAllCountriesAssigned($query)
    {

        $su = false;

        if (auth()->user()) {
            $su = $this->isSuperUsuario(auth()->user()->id);
        }

        if (! $su && auth()->user()) {
            $codeCountries = auth()->user()->ownedUserCountry->pluck('country_id')->toArray();

            return $query->whereIn('id', $codeCountries)->where('estatus', 1);
        } else {
            return $query->where('estatus', 1);
        }
    }
}
