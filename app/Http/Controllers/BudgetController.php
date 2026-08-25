<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBudgetRequest;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                ...$this->budgetService->overview($user),
            ],
        ]);
    }

    public function update(UpdateBudgetRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $budget = $this->budgetService->adjustBalance($user, (int) $request->validated('amount'), (string) $request->validated('description'));

        return response()->json([
            'data' => [
                'amount' => $budget->amount,
            ],
            'message' => 'Budget updated.',
        ]);
    }
}
