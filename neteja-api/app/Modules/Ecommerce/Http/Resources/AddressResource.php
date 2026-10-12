<?php

namespace App\Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'commune' => $this->commune,
            'address_line' => $this->address_line,
            'additional_info' => $this->additional_info,
            'is_default' => $this->is_default,
        ];
    }
}
