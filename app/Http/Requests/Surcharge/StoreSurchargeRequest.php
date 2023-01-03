<?php

namespace App\Http\Requests\Surcharge;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurchargeRequest extends FormRequest
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
        $rules = [
                'name' => ['required', Rule::unique('schedules', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
        ];
        if ($this->request->get('type') == "percentage"){
            $rules['amount'] = 'required | numeric | min:0 | max:100 ';
        }

        if ($this->request->get('type') == "flat"){
            $rules['amount'] = 'required | numeric ';
        }

        return $rules;

    }
//    public function messages (): array
//    {
////        return [
////            'name.required' => 'Surcharge Name is Required!',
////            'name.unique' => 'Surcharge Name not be Repeated!',
////            'amount.required' => 'Surcharge amount is Required!',
////            'amount.min' => 'Surcharge amount never be less then 0',
////            'amount.max' => 'Surcharge amount never be greater then 100',
////        ];
//    }

}
