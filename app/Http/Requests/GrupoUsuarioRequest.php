<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesResourceWrite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrupoUsuarioRequest extends FormRequest
{
    use AuthorizesResourceWrite;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->canWriteResource('grupos_trabajo');
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
            'nombre' => ['required', 'max:100', Rule::unique('grupos_trabajos', 'nombre')->ignore($id)],
            'city_id' => [Rule::requiredIf(($id === 0))],
            'reference' => ['max:200'],
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'Requerido', // custom message
            'nombre.unique' => 'El nombre ya fue tomado', // custom message
        ];
    }
}
