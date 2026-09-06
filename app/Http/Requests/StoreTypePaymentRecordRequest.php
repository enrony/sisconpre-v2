<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesResourceWrite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTypePaymentRecordRequest extends FormRequest
{
    use AuthorizesResourceWrite;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->canWriteResource('type_payment_record');
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
            'description' => ['required', 'max:50'],
            'code' => ['required',  Rule::unique('type_payment_records', 'code')->ignore($id)],
        ];
    }
}
