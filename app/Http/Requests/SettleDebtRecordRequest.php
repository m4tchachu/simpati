<?php

namespace App\Http\Requests;

use App\Enums\DebtStatus;
use Illuminate\Foundation\Http\FormRequest;

class SettleDebtRecordRequest extends FormRequest
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
        // Validation only - status check moved to controller
        return [];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'Status',
        ];
    }

    /**
     * Get debt record untuk settlement
     */
    public function getDebtRecord()
    {
        return $this->route('debtRecord');
    }

    /**
     * Get settled timestamp (untuk audit)
     */
    public function getSettledAt()
    {
        return now();
    }
}
