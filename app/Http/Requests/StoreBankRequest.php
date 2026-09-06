<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesResourceWrite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankRequest extends FormRequest
{
    use AuthorizesResourceWrite;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->canWriteResource('banks');
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
            'code' => ['required', 'max:5',
                // Rule::unique( 'payment_methods', 'code' )->ignore( $id ),
            ],
            'description' => 'required|max:50',
        ];
    }
}
