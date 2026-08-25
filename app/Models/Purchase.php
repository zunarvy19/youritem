<?php

namespace App\Models;

use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $wishlist_item_id
 * @property int $actual_price
 * @property Carbon $purchased_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property WishlistItem $wishlistItem
 */
#[Fillable(['user_id', 'wishlist_item_id', 'actual_price', 'purchased_at'])]
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actual_price' => 'integer',
            'purchased_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<WishlistItem, $this> */
    public function wishlistItem(): BelongsTo
    {
        return $this->belongsTo(WishlistItem::class);
    }

    /** @return HasOne<BudgetTransaction, $this> */
    public function budgetTransaction(): HasOne
    {
        return $this->hasOne(BudgetTransaction::class);
    }
}
