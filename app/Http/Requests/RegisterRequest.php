<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name'              => 'required|string|max:100|min:3|regex:/^[a-zA-Z\s]+$/',
            'email'             => 'required|email|unique:users,email|regex:/^[a-zA-Z0-9.@]+$/',
            'password'          => 'required|string|min:8|confirmed',
            'phone'             => 'required|string|regex:/^[6-9][0-9]{9}$/',
            'profile_image'     => 'required|nullable|image|mimes:jpg,jpeg,png|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name field may only contain letters and spaces.',
            'email.regex' => 'The email may only contain letters, numbers, dots (.), and the at sign (@).',
            'phone.regex' => 'Phone number must be 10 digits and start with 6, 7, 8, or 9.',
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
