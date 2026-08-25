<?php

namespace App\Services;

use App\Enums\BudgetTransactionType;
use App\Enums\WishlistStatus;
use App\Models\Budget;
use App\Models\Purchase;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    /**
     * Execute a purchase atomically:
     * lock item row -> validate status/ownership -> lock budget row ->
     * validate funds -> create purchase -> mark item PURCHASED -> decrement budget.
     *
     * Recommendation data is never trusted; everything is revalidated here.
     *
     * @throws ValidationException
     */
    public function purchase(
        User $user,
        WishlistItem $item,
        int $actualPrice,
        ?Carbon $purchasedAt = null,
    ): Purchase {
        return DB::transaction(function () use ($user, $item, $actualPrice, $purchasedAt): Purchase {
            /** @var WishlistItem|null $locked */
            $locked = WishlistItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                throw ValidationException::withMessages([
                    'item' => 'Wishlist item not found.',
                ]);
            }

            if ($locked->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'item' => 'You do not own this wishlist item.',
                ]);
            }

            if ($locked->status !== WishlistStatus::Active) {
                throw ValidationException::withMessages([
                    'item' => 'Only active wishlist items can be purchased.',
                ]);
            }

            /** @var Budget|null $budget */
            $budget = Budget::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $availableBudget = $budget === null ? 0 : $budget->amount;

            if ($actualPrice > $availableBudget) {
                throw ValidationException::withMessages([
                    'actual_price' => __('Insufficient budget. Available :available, needed :needed.', [
                        'available' => $availableBudget,
                        'needed' => $actualPrice,
                    ]),
                ]);
            }

            $purchase = Purchase::create([
                'user_id' => $user->id,
                'wishlist_item_id' => $locked->id,
                'actual_price' => $actualPrice,
                'purchased_at' => $purchasedAt ?? now(),
            ]);

            $locked->update(['status' => WishlistStatus::Purchased]);

            if ($budget !== null) {
                $budget->amount = max(0, $budget->amount - $actualPrice);
                $budget->save();
            }

            $user->budgetTransactions()->create([
                'purchase_id' => $purchase->id,
                'type' => BudgetTransactionType::Expense,
                'amount' => -$actualPrice,
                'description' => $locked->name,
                'occurred_at' => $purchase->purchased_at,
            ]);

            return $purchase->refresh();
        });
    }
}
