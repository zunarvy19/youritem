<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    public function store(StorePurchaseRequest $request, WishlistItem $wishlistItem): JsonResponse
    {
        Gate::authorize('purchase', $wishlistItem);

        /** @var User $user */
        $user = $request->user();

        $purchasedAt = $request->filled('purchased_at')
            ? Carbon::parse((string) $request->input('purchased_at'))
            : null;

        $purchase = $this->purchaseService->purchase(
            $user,
            $wishlistItem,
            (int) $request->validated('actual_price'),
            $purchasedAt,
        );

        return response()->json([
            'data' => new PurchaseResource($purchase->load('wishlistItem.category')),
            'message' => 'Purchase completed.',
        ], 201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $purchases = $user->purchases()
            ->with('wishlistItem.category')
            ->orderByDesc('purchased_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return PurchaseResource::collection($purchases);
    }
}
