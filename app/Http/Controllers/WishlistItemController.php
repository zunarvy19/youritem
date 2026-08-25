<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\WishlistStatus;
use App\Http\Requests\StoreWishlistItemRequest;
use App\Http\Requests\UpdateWishlistItemRequest;
use App\Http\Resources\WishlistItemResource;
use App\Jobs\FetchWishlistPreview;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WishlistItemController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $query = $user->wishlistItems()
            ->with('category')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = mb_strtolower(trim((string) $request->input('search')));
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', (string) $request->input('priority')))
            ->when($request->filled('purpose'), fn ($query) => $query->where('purpose', (string) $request->input('purpose')))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', (string) $request->input('status')),
                fn ($query) => $query->whereIn('status', [WishlistStatus::Active, WishlistStatus::Archived]),
            );

        $this->applySort($query, (string) $request->input('sort', 'newest'));

        return WishlistItemResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreWishlistItemRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $item = $user->wishlistItems()->create(array_merge(
            $request->validated(),
            ['status' => WishlistStatus::Active],
        ));

        if ($item->product_url !== null) {
            FetchWishlistPreview::dispatch($item->id, $item->product_url);
        }

        return response()->json([
            'data' => new WishlistItemResource($item->load('category')),
            'message' => 'Wishlist item added.',
        ], 201);
    }

    public function show(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        Gate::authorize('view', $wishlistItem);

        return response()->json([
            'data' => new WishlistItemResource($wishlistItem->load('category')),
        ]);
    }

    public function update(UpdateWishlistItemRequest $request, WishlistItem $wishlistItem): JsonResponse
    {
        Gate::authorize('update', $wishlistItem);
        $this->assertEditable($wishlistItem);

        $oldUrl = $wishlistItem->product_url;
        $wishlistItem->update($request->validated());
        if ($wishlistItem->product_url !== $oldUrl) {
            $wishlistItem->update([
                'preview_title' => null, 'preview_description' => null, 'preview_image_url' => null,
                'preview_site_name' => null, 'preview_fetched_at' => null,
            ]);
            if ($wishlistItem->product_url !== null) {
                FetchWishlistPreview::dispatch($wishlistItem->id, $wishlistItem->product_url);
            }
        }

        return response()->json([
            'data' => new WishlistItemResource($wishlistItem->load('category')),
            'message' => 'Wishlist item updated.',
        ]);
    }

    public function archive(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        Gate::authorize('archive', $wishlistItem);

        if ($wishlistItem->status !== WishlistStatus::Active) {
            return $this->invalidStatusResponse(
                $wishlistItem,
                'Only active items can be archived.',
            );
        }

        $wishlistItem->update(['status' => WishlistStatus::Archived]);

        return response()->json([
            'data' => new WishlistItemResource($wishlistItem),
            'message' => 'Wishlist item archived.',
        ]);
    }

    public function restore(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        Gate::authorize('archive', $wishlistItem);

        if ($wishlistItem->status !== WishlistStatus::Archived) {
            return $this->invalidStatusResponse(
                $wishlistItem,
                'Only archived items can be restored.',
            );
        }

        $wishlistItem->update(['status' => WishlistStatus::Active]);

        return response()->json([
            'data' => new WishlistItemResource($wishlistItem),
            'message' => 'Wishlist item restored.',
        ]);
    }

    /**
     * @param  HasMany<WishlistItem, User>|Builder<WishlistItem>  $query
     */
    private function applySort(HasMany|Builder $query, string $sort): void
    {
        match ($sort) {
            'priority' => $query->orderByRaw(
                "CASE priority WHEN '".Priority::High->value."' THEN 1 WHEN '".Priority::Medium->value."' THEN 2 ELSE 3 END ASC",
            ),
            'price' => $query->orderBy('estimated_price', 'asc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    private function assertEditable(WishlistItem $wishlistItem): void
    {
        if ($wishlistItem->status === WishlistStatus::Purchased) {
            abort(422, 'Purchased items can no longer be modified.');
        }
    }

    private function invalidStatusResponse(WishlistItem $wishlistItem, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'current_status' => $wishlistItem->status->value,
        ], 422);
    }
}
