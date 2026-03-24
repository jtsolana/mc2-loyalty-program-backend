<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyverseItemsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'name' => $this['item_name'],
            'category_id' => $this['category_id'],
            'image_url' => $this['image_url'],
            'variants' => collect($this['variants'])->map(fn (array $variant) => [
                'variant_id' => $variant['variant_id'],
                'option1_value' => $variant['option1_value'],
                'option2_value' => $variant['option2_value'],
                'option3_value' => $variant['option3_value'],
            ])->toArray(),
        ];
    }
}
