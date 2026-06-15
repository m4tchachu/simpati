<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya admin yang bisa membuat student
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nim' => [
                'required',
                'string',
                'unique:users,nim',
                'regex:/^[A-Za-z0-9]{1,20}$/',
                'max:20',
            ],
            'name' => [
                'required',
                'string',
                'regex:/^[a-zA-Z\s\-\.\']+$/',
                'min:3',
                'max:255',
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'unique:users,email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
                'max:255',
            ],
            'study_program_id' => [
                'required',
                'integer',
                'exists:study_programs,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nim.required' => 'NIM wajib diisi.',
            'nim.string' => 'NIM harus berupa teks.',
            'nim.unique' => 'NIM sudah terdaftar di sistem.',
            'nim.regex' => 'NIM hanya boleh mengandung huruf dan angka (A-Z, 0-9).',
            'nim.max' => 'NIM maksimal 20 karakter.',
            
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.string' => 'Nama lengkap harus berupa teks.',
            'name.regex' => 'Nama lengkap hanya boleh mengandung huruf, spasi, tanda hubung, dan apostrof.',
            'name.min' => 'Nama lengkap minimal 3 karakter.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar di sistem.',
            'email.max' => 'Email maksimal 255 karakter.',
            
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.max' => 'Password maksimal 255 karakter.',
            
            'study_program_id.required' => 'Program studi wajib dipilih.',
            'study_program_id.integer' => 'Program studi tidak valid.',
            'study_program_id.exists' => 'Program studi yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nim' => 'NIM',
            'name' => 'Nama Lengkap',
            'email' => 'Email',
            'password' => 'Password',
            'study_program_id' => 'Program Studi',
        ];
    }

    /**
     * Get email from request (lowercased)
     */
    public function getEmail(): string
    {
        return strtolower($this->input('email'));
    }

    /**
     * Get NIM from request (uppercase)
     */
    public function getNim(): string
    {
        return strtoupper($this->input('nim'));
    }
}
