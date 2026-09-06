<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesResourceWrite;
use Illuminate\Foundation\Http\FormRequest;

class StoreCountryHolidayRequest extends FormRequest
{
    use AuthorizesResourceWrite;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->canWriteResource('festivos');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:100',
            'date' => 'required|date',
            'country_id' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'date.required' => 'Día festivo es requerido',
        ];
    }
}
