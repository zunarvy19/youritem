<?php

namespace App\Models;

use App\Enums\BudgetTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $purchase_id
 * @property BudgetTransactionType $type
 * @property int $amount
 * @property string|null $description
 * @property Carbon $occurred_at
 * @property Purchase|null $purchase
 */
#[Fillable(['user_id', 'purchase_id', 'type', 'amount', 'description', 'occurred_at'])]
class BudgetTransaction extends Model
{
    protected function casts(): array
    {
        return ['type' => BudgetTransactionType::class, 'amount' => 'integer', 'occurred_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
