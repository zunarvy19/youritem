<?php

namespace App\Http\Resources;

use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property WishlistItem|null $wishlistItem
 * @property int $actual_price
 * @property Carbon $purchased_at
 */
class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wishlist_item' => [
                'id' => $this->wishlistItem?->id,
                'name' => $this->wishlistItem?->name,
                'category' => [
                    'id' => $this->wishlistItem?->category?->id,
                    'name' => $this->wishlistItem?->category?->name,
                ],
                'priority' => $this->wishlistItem?->priority?->value,
                'purpose' => $this->wishlistItem?->purpose?->value,
                'estimated_price' => $this->wishlistItem?->estimated_price,
            ],
            'actual_price' => $this->actual_price,
            'purchased_at' => $this->purchased_at->toIso8601String(),
        ];
    }
}
