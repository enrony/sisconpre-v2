<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryHoliday extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'country_id',
        'grupos_trabajos_user_id',
        'date',
        'estatus',
        'year',
    ];

    protected $with = ['country'];
    // protected $appends = ["created", "updated"];

    public function scopeWhereCountriesActive($query, $countriesID)
    {
        return (new static)->whereIn('country_id', $countriesID);
    }

    // public function getCreatedAttribute(){
    //     return $this->created;
    // }

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = date('Y-m-d', strtotime($value));
    }

    public function scopeCriterioYear($query)
    {
        $CountryAll = Country::WhereAllCountriesAssigned()->get();
        $rangosYear = [date('Y') - 1, date('Y'), date('Y') + 1];

        return $query->whereIn('year', $rangosYear)->WhereCountriesActive($CountryAll->pluck('id')->toArray());
    }

    public function lista($request)
    {

        $CountryAll = Country::WhereAllCountriesAssigned()->get();

        $query1 = CountryHoliday::query()->selectRaw("country_holidays.*, date_format(country_holidays.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format(country_holidays.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
            ->WhereCountriesActive($CountryAll->pluck('id')->toArray());
        if (isset($request->term)) {
            $query1 = $query1->when($request->term, function ($query, $term) {
                $query->where('country_holidays.name', 'LIKE', '%'.$term.'%');
            });
        }
        $query1 = $query1->with(['country'])
            ->get();

        $query1 = $query1->map(function ($item) {
            $item->created = date('Y-m-d H:i', strtotime($item->created_at));
            $item->updated = date('Y-m-d H:i', strtotime($item->updated_at));

            return $item;
        });

        $queryAll = collect($query1);

        return $queryAll;

    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
