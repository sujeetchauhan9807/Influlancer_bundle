<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;

class CampaignUpdateRequest extends FormRequest
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
        'status'                => 'sometimes|string|regex:/^[a-zA-Z\s]+$/',
        'budget'                => 'required|numeric|min:0',
        'currency'              => 'nullable|string|size:3|regex:/^[A-Z]{3}$/',
        'require_influencers'   => 'required|integer',
        'commission_amount'     => 'nullable|numeric|min:0',
        'start_date'            => 'required|date',
        'end_date'              => 'required|date|after:start_date',
    ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name field may only contain letters and spaces.',
            'currency.regex' => 'The currency field may only contain letters Ex- INR , USD.',
            'status.regex' => 'The status field may only contain letters Ex- pending , active, completed, cancelled',
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
