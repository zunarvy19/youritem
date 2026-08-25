<?php

namespace App\Services;

use App\Enums\BudgetTransactionType;
use App\Models\Budget;
use App\Models\BudgetTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetService
{
    public function getOrCreateBudget(User $user): Budget
    {
        return $user->budget()->firstOrNew();
    }

    public function getAvailableBudget(User $user): int
    {
        /** @var Budget|null $budget */
        $budget = $user->budget()->first();

        return $budget === null ? 0 : $budget->amount;
    }

    public function updateBudget(User $user, int $amount): Budget
    {
        return $this->adjustBalance($user, $amount, 'Balance correction');
    }

    public function adjustBalance(User $user, int $targetAmount, string $description): Budget
    {
        return DB::transaction(function () use ($user, $targetAmount, $description): Budget {
            $budget = Budget::query()->firstOrCreate(['user_id' => $user->id], ['amount' => 0]);
            $budget = Budget::query()->whereKey($budget)->lockForUpdate()->firstOrFail();
            $difference = $targetAmount - $budget->amount;

            if ($difference !== 0) {
                $user->budgetTransactions()->create([
                    'type' => BudgetTransactionType::Adjustment,
                    'amount' => $difference,
                    'description' => $description,
                    'occurred_at' => now(),
                ]);
            }

            $budget->update(['amount' => $targetAmount]);

            return $budget->refresh();
        });
    }

    public function record(User $user, BudgetTransactionType $type, int $amount, ?string $description, ?Carbon $occurredAt = null): BudgetTransaction
    {
        return DB::transaction(function () use ($user, $type, $amount, $description, $occurredAt): BudgetTransaction {
            $budget = Budget::query()->firstOrCreate(['user_id' => $user->id], ['amount' => 0]);
            $budget = Budget::query()->whereKey($budget)->lockForUpdate()->firstOrFail();
            $signedAmount = $type === BudgetTransactionType::Expense ? -$amount : $amount;

            if ($budget->amount + $signedAmount < 0) {
                throw ValidationException::withMessages(['amount' => 'Insufficient balance.']);
            }

            $transaction = BudgetTransaction::query()->create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $signedAmount,
                'description' => $description,
                'occurred_at' => $occurredAt ?? now(),
            ]);
            $budget->update(['amount' => $budget->amount + $signedAmount]);

            return $transaction;
        });
    }

    /** @return array<string, mixed> */
    public function overview(User $user): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $query = BudgetTransaction::query()->whereBelongsTo($user)->whereBetween('occurred_at', [$start, $end]);

        return [
            'amount' => $this->getAvailableBudget($user),
            'month' => now()->format('Y-m'),
            'income' => (int) (clone $query)->where('type', BudgetTransactionType::Income)->sum('amount'),
            'expense' => abs((int) (clone $query)->where('type', BudgetTransactionType::Expense)->sum('amount')),
            'transactions' => BudgetTransaction::query()->whereBelongsTo($user)->with('purchase.wishlistItem')->latest('occurred_at')->limit(20)->get()->map(fn (BudgetTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type->value,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'occurred_at' => $transaction->occurred_at->toIso8601String(),
                'purchase' => $transaction->purchase === null ? null : ['id' => $transaction->purchase->id, 'wishlist_item_name' => $transaction->purchase->wishlistItem->name],
            ])->values(),
        ];
    }

    public function canAfford(User $user, int $estimatedPrice): bool
    {
        return $this->getAvailableBudget($user) >= $estimatedPrice;
    }
}
