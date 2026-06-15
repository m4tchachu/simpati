<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->whenLoaded('type', fn () => [
                'id' => $this->type->id,
                'code' => $this->type->code,
                'name' => $this->type->name,
            ]),
            'notification_type_code' => $this->whenLoaded('type', fn () => $this->type->code),
            'debt_record' => $this->whenLoaded('debtRecord', fn () => [
                'id' => $this->debtRecord->id,
                'amount' => (float) $this->debtRecord->amount,
                'status' => $this->debtRecord->status->value,
                'status_label' => $this->debtRecord->status->label(),
            ]),
            'data' => $this->data,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
