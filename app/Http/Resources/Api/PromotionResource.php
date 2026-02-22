<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hashed_id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'thumbnail_url' => $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null,
            'content' => $this->content,
            'type' => $this->type,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
        ];
    }
}
