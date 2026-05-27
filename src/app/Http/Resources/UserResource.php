<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'email'        => $this->email,
            'name'         => $this->name,
            'role'         => $this->role,
            'created_at'   => $this->created_at?->toIso8601String(),
            'orders_count' => $this->when(isset($this->orders_count), $this->orders_count),
            'can_edit'     => $this->when($this->resource->getAttribute('can_edit') !== null, $this->resource->getAttribute('can_edit')),
        ];
    }
}
