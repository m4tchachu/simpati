<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                'min:6',
                'max:255',
            ],
            'new_password' => [
                'required',
                'string',
                'min:6',
                'max:255',
                'confirmed',
                'different:current_password',
            ],
            'new_password_confirmation' => [
                'required',
                'string',
                'min:6',
                'max:255',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Password saat ini harus diisi',
            'current_password.string' => 'Password saat ini harus berupa teks',
            'current_password.min' => 'Password saat ini minimal 6 karakter',
            'current_password.max' => 'Password saat ini maksimal 255 karakter',

            'new_password.required' => 'Password baru harus diisi',
            'new_password.string' => 'Password baru harus berupa teks',
            'new_password.min' => 'Password baru minimal 6 karakter',
            'new_password.max' => 'Password baru maksimal 255 karakter',
            'new_password.confirmed' => 'Password baru tidak cocok dengan konfirmasinya',
            'new_password.different' => 'Password baru harus berbeda dengan password saat ini',

            'new_password_confirmation.required' => 'Konfirmasi password baru harus diisi',
            'new_password_confirmation.string' => 'Konfirmasi password baru harus berupa teks',
            'new_password_confirmation.min' => 'Konfirmasi password baru minimal 6 karakter',
            'new_password_confirmation.max' => 'Konfirmasi password baru maksimal 255 karakter',
        ];
    }

    /**
     * Get old password (current password)
     *
     * @return string
     */
    public function getOldPassword(): string
    {
        return $this->input('current_password');
    }

    /**
     * Get new password
     *
     * @return string
     */
    public function getNewPassword(): string
    {
        return $this->input('new_password');
    }
}
