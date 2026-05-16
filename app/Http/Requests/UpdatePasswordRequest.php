<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'current_password' => [
                'required',
                'string',
                'current_password:web',
            ],
            'new_password' => [
                'required',
                'string',
                'min:12',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/',
                'confirmed',
                'different:current_password',
            ],
            'new_password_confirmation' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'current_password.current_password' => 'The provided password does not match your current password.',
            'new_password.min' => 'The password must be at least 12 characters.',
            'new_password.regex' => 'Password must contain uppercase, lowercase, number, and special character.',
            'new_password.confirmed' => 'The password confirmation does not match.',
            'new_password.different' => 'The new password must be different from the current password.',
        ];
    }
}
