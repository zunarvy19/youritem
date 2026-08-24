<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ShoppingRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShoppingController extends Controller
{
    public function __construct(
        private readonly ShoppingRecommendationService $recommendationService,
    ) {}

    public function recommend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->recommendationService->recommend($user),
        ]);
    }
}
