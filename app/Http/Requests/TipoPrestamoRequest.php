<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesResourceWrite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoPrestamoRequest extends FormRequest
{
    use AuthorizesResourceWrite;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->canWriteResource('tipo_prestamo');
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
            'cantidad' => ['required', 'integer', 'min:1', 'max:100',

            ],
            'descripcion' => ['required',  Rule::unique('tipo_prestamos', 'descripcion')->ignore($id)],
            'tipo_frecuencia_prestamo_id' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'cantidad.required' => 'Requerido', // custom message
            'cantidad.min' => "Indicar minimo '1'", // custom message
            'cantidad.max' => 'La cantidad no puede ser mayor a 100', // custom message
            'cantidad.integer' => 'Debe indicar un valor numerico', // custom message
            'nombre.required' => 'Requerido', // custom message
            'nombre.unique' => 'El nombre ya fue tomado', // custom message
            'tipo.required' => 'Requerido', // custom message
        ];
    }
}
