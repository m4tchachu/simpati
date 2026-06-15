<?php

namespace App\Http\Requests;

use App\Enums\DebtStatus;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmDebtRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya counterpart yang bisa confirm
        $debtRecord = $this->route('debtRecord');
        return $this->user() && $debtRecord->counterpart_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $debtRecord = $this->route('debtRecord');

        return [
            // Validasi: Status harus pending
            'status' => [
                function ($attribute, $value, $fail) use ($debtRecord) {
                    if ($debtRecord->status !== DebtStatus::PENDING) {
                        $fail('Catatan hanya bisa dikonfirmasi jika statusnya masih "Menunggu Konfirmasi". Status saat ini: ' . $debtRecord->status->label());
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
     * Get debt record untuk confirmation
     */
    public function getDebtRecord()
    {
        return $this->route('debtRecord');
    }
}
