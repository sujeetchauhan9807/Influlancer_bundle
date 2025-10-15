<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'brand_id'              => 'required|integer|exists:brands,id',
        'campaign_type_id'      => 'required|integer|exists:campaign_types,id',
        'category_id'           => 'required|integer|exists:categories,id',
        'title'                 => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
        'description'           => 'nullable|string',
        'budget'                => 'required|numeric|min:0',
        'currency'              => 'nullable|string|size:3|regex:/^[A-Z]{3}$/',  //Ex- INR , USD
        'require_influencers'   => 'required|integer',
        'start_date'            => 'required|date|after_or_equal:today',
        'end_date'              => 'required|date|after:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name field may only contain letters and spaces.',
            'currency.regex' => 'The currency field may only contain letters Ex- INR , USD.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422));
    }
}
