<?php

namespace App\Http\Requests;

use App\Enums\DebtStatus;
use Illuminate\Foundation\Http\FormRequest;

class SettleDebtRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Creator atau counterpart bisa mark as settled
        $debtRecord = $this->route('debtRecord');
        return $this->user() && (
            $debtRecord->creator_id === $this->user()->id ||
            $debtRecord->counterpart_id === $this->user()->id
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Validasi: Status harus active
            'status' => [
                function ($attribute, $value, $fail) {
                    $debtRecord = $this->route('debtRecord');
                    if ($debtRecord->status !== DebtStatus::ACTIVE) {
                        $fail('Catatan hanya bisa ditandai lunas jika statusnya "Aktif". Status saat ini: ' . $debtRecord->status->label());
                    }
                },
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trigger validation dengan dummy value
        $this->merge([
            'status' => true,
        ]);
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
