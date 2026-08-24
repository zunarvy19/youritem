<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($user, $amount): Budget {
            /** @var Budget $budget */
            $budget = $user->budget()->firstOrNew();
            $budget->amount = $amount;
            $budget->save();

            return $budget;
        });
    }

    public function canAfford(User $user, int $estimatedPrice): bool
    {
        return $this->getAvailableBudget($user) >= $estimatedPrice;
    }
}
