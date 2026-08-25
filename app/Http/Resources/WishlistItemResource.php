<?php

namespace App\Http\Resources;

use App\Enums\Priority;
use App\Enums\Purpose;
use App\Enums\WishlistStatus;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Category|null $category
 * @property Priority $priority
 * @property Purpose $purpose
 * @property int $estimated_price
 * @property string|null $notes
 * @property string|null $product_url
 * @property string|null $preview_title
 * @property string|null $preview_description
 * @property string|null $preview_image_url
 * @property string|null $preview_site_name
 * @property Carbon|null $preview_fetched_at
 * @property WishlistStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WishlistItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'priority' => $this->priority->value,
            'purpose' => $this->purpose->value,
            'estimated_price' => $this->estimated_price,
            'notes' => $this->notes,
            'product_url' => $this->product_url,
            'preview' => $this->product_url === null ? null : [
                'title' => $this->preview_title,
                'description' => $this->preview_description,
                'image_url' => $this->preview_image_url,
                'site_name' => $this->preview_site_name ?? parse_url($this->product_url, PHP_URL_HOST),
                'fetched_at' => $this->preview_fetched_at?->toIso8601String(),
            ],
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
