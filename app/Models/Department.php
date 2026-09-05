<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    public $fillable = [
        'nombre',
        'country_id',
        'grupos_trabajos_user_id',
    ];

    protected $with = ['country'];

    public function scopeWhereCountriesActive($query, $countriesID)
    {
        return (new static)->whereIn('country_id', $countriesID);
    }

    public static function lista($request)
    {

        $CountryAll = Country::WhereAllCountriesAssigned()->get();

        $query1 = (new static)::selectRaw("departments.*, date_format(departments.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format(departments.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
            ->WhereCountriesActive($CountryAll->pluck('id')->toArray());
        if (isset($request->term)) {
            $query1 = $query1->when($request->term, function ($query, $term) {
                $query->where('departments.nombre', 'LIKE', '%'.$term.'%');
            });
        }
        $query1 = $query1->with(['country'])
            ->get();

        $queryAll = collect($query1);

        return $queryAll;
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
