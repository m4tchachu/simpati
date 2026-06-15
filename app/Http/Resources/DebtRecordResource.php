<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'transaction_date' => $this->transaction_date,
            'due_date' => $this->due_date,
            'confirmed_at' => $this->confirmed_at,
            'rejected_at' => $this->rejected_at,
            'rejection_reason' => $this->rejection_reason,
            'settled_at' => $this->settled_at,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
                'nim' => $this->creator->nim,
            ]),
            'counterpart' => $this->whenLoaded('counterpart', fn () => [
                'id' => $this->counterpart->id,
                'name' => $this->counterpart->name,
                'email' => $this->counterpart->email,
                'nim' => $this->counterpart->nim,
            ]),
            'status_changes_count' => $this->whenLoaded('statusChanges', fn () => $this->statusChanges->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
