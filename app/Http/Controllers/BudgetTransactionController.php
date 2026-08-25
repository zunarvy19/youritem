<?php

namespace App\Http\Controllers;

use App\Enums\BudgetTransactionType;
use App\Http\Requests\StoreBudgetTransactionRequest;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;

class BudgetTransactionController extends Controller
{
    public function __construct(private readonly BudgetService $budgetService) {}

    public function store(StoreBudgetTransactionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $transaction = $this->budgetService->record(
            $user,
            BudgetTransactionType::from((string) $request->validated('type')),
            (int) $request->validated('amount'),
            $request->validated('description'),
            $request->date('occurred_at'),
        );

        return response()->json(['data' => ['id' => $transaction->id], 'message' => 'Transaction recorded.'], 201);
    }
}
