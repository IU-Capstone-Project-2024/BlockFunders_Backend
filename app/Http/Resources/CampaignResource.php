<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            'description' => $this->description,
            'target_amount' => $this->target_amount,
            'collected_amount' => $this->collected_amount,
            'status' => $this->status,
            'user' => $this->user,
            'category' => new CampaignCategoryResource($this->whenLoaded('category')),
            'campaign_update' => $this->whenLoaded('updates', fn () => CampaignUpdateResource::collection($this->updates)),
            'image' => $this->image,
            'deadline' => $this->deadline,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
