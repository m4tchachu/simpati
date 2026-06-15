<?php

namespace App\Http\Requests;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use App\Models\DebtRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDebtRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya creator yang bisa update
        $debtRecord = $this->route('debtRecord');
        return $this->user() && $debtRecord->creator_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $debtRecord = $this->route('debtRecord');

        // Validasi: Hanya bisa edit jika status pending
        if ($debtRecord->status !== DebtStatus::PENDING) {
            return [
                'status' => ['prohibited'],
            ];
        }

        return [
            'counterpart_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn($this->user()->id), // Creator tidak boleh sama dengan counterpart
            ],
            'type' => [
                'required',
                'string',
                Rule::enum(DebtType::class),
            ],
            'amount' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0.01',
                'max:999999999.99',
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'transaction_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
            'due_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:transaction_date',
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $debtRecord = $this->route('debtRecord');

        // Jika status bukan pending, tambah error
        if ($debtRecord->status !== DebtStatus::PENDING) {
            $this->merge([
                'status_error' => true,
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.prohibited' => 'Catatan hanya bisa diubah jika status masih "Menunggu Konfirmasi".',
            
            'counterpart_id.required' => 'Pilih pihak kedua.',
            'counterpart_id.integer' => 'Pihak kedua tidak valid.',
            'counterpart_id.exists' => 'Pengguna yang dipilih tidak ditemukan.',
            'counterpart_id.not_in' => 'Anda tidak bisa membuat catatan dengan diri sendiri.',
            
            'type.required' => 'Jenis hutang/piutang wajib dipilih.',
            'type.string' => 'Jenis hutang/piutang tidak valid.',
            'type.enum' => 'Jenis hutang/piutang hanya boleh: debt (hutang) atau receivable (piutang).',
            
            'amount.required' => 'Nominal wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.decimal' => 'Nominal boleh memiliki maksimal 2 desimal.',
            'amount.min' => 'Nominal minimal Rp 0.01.',
            'amount.max' => 'Nominal maksimal Rp 999.999.999,99.',
            
            'description.required' => 'Deskripsi wajib diisi.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'description.min' => 'Deskripsi minimal 10 karakter.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date' => 'Format tanggal transaksi tidak valid.',
            'transaction_date.date_format' => 'Format tanggal harus: YYYY-MM-DD.',
            'transaction_date.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
            'due_date.date' => 'Format tanggal jatuh tempo tidak valid.',
            'due_date.date_format' => 'Format tanggal harus: YYYY-MM-DD.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo harus sama atau setelah tanggal transaksi.',
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
            'counterpart_id' => 'Pihak Kedua',
            'type' => 'Jenis Hutang/Piutang',
            'amount' => 'Nominal',
            'description' => 'Deskripsi',
            'transaction_date' => 'Tanggal Transaksi',
            'due_date' => 'Tanggal Jatuh Tempo',
        ];
    }

    /**
     * Get amount as float
     */
    public function getAmount(): float
    {
        return (float) $this->input('amount');
    }

    /**
     * Get type as enum
     */
    public function getType(): DebtType
    {
        return DebtType::from($this->input('type'));
    }
}
