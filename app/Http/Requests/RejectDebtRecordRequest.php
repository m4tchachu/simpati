<?php

namespace App\Http\Requests;

use App\Enums\DebtStatus;
use Illuminate\Foundation\Http\FormRequest;

class RejectDebtRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled in Controller via Policy
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
            'rejection_reason' => [
                'required',
                'string',
                'min:10',
                'max:500',
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
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.string' => 'Alasan penolakan harus berupa teks.',
            'rejection_reason.min' => 'Alasan penolakan minimal 10 karakter.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 500 karakter.',
            
            'status.errors' => 'Catatan hanya bisa ditolak jika statusnya masih "Menunggu Konfirmasi".',
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
            'rejection_reason' => 'Alasan Penolakan',
        ];
    }

    /**
     * Get rejection reason
     */
    public function getRejectionReason(): string
    {
        return $this->input('rejection_reason');
    }

    /**
     * Get debt record untuk rejection
     */
    public function getDebtRecord()
    {
        return $this->route('debtRecord');
    }
}
