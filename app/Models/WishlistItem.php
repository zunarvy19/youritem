<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\Purpose;
use App\Enums\WishlistStatus;
use Database\Factories\WishlistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property string $name
 * @property Priority $priority
 * @property Purpose $purpose
 * @property int $estimated_price
 * @property string|null $notes
 * @property WishlistStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'category_id',
    'name',
    'priority',
    'purpose',
    'estimated_price',
    'notes',
    'status',
])]
class WishlistItem extends Model
{
    /** @use HasFactory<WishlistItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'purpose' => Purpose::class,
            'status' => WishlistStatus::class,
            'estimated_price' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasOne<Purchase, $this> */
    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class);
    }
}
