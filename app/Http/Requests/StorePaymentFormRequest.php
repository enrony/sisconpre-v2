<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesResourceWrite;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentFormRequest extends FormRequest
{
    use AuthorizesResourceWrite;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->canWriteResource('payment_forms');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
