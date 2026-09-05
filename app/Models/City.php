<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    public $fillable = [
        'nombre',
        'country_id',
        'department_id',
        'grupos_trabajos_user_id',
    ];

    protected $with = ['department'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
    ];

    public function scopeWhereCountriesActive($query, $countriesID)
    {
        return (new static)->whereIn('country_id', $countriesID);
    }

    public static function lista($request)
    {

        $CountryAll = Country::WhereAllCountriesAssigned()->get();

        $query1 = (new static)::selectRaw("cities.*, date_format(cities.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format(cities.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
            ->WhereCountriesActive($CountryAll->pluck('id')->toArray())
            ->when($request->term, function ($query, $term) {
                $query->where('cities.nombre', 'LIKE', '%'.$term.'%');
            })
            ->with(['country', 'department'])
            ->get()->transform(function ($item) {
                $item->updated = '';
                if ($item->updated_at) {
                    $item->updated = $item->updated_at->format('Y-m-d H:i:s');
                }

                return $item;
            })->toArray();

        $queryAll = collect($query1);

        return $queryAll;

    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
