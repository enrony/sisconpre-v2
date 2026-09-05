<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $id = $this->id;

        return [
            'name' => 'required|max:100',
            'description' => 'nullable|max:100',
        ];
    }

    public function messages()
    {
        return [];
        // return [
        //     'cantidad.required'=> 'Requerido', // custom message
        //     'cantidad.min'=> "Indicar minimo '1'", // custom message
        //     'cantidad.max'=> "La cantidad no puede ser mayor a 100", // custom message
        //     'cantidad.integer'=> "Debe indicar un valor numerico", // custom message
        //     'nombre.required'=> 'Requerido', // custom message
        //     'nombre.unique'=> 'El nombre ya fue tomado', // custom message
        //     'tipo.required'=> 'Requerido' // custom message
        // ];
    }
}
