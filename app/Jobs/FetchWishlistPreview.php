<?php

namespace App\Jobs;

use App\Models\WishlistItem;
use App\Services\ProductPreviewService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchWishlistPreview implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public int $wishlistItemId, public string $productUrl) {}

    public function handle(ProductPreviewService $preview): void
    {
        $item = WishlistItem::find($this->wishlistItemId);
        if ($item === null || $item->product_url !== $this->productUrl) {
            return;
        }
        $item->update($preview->fetch($this->productUrl));
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Wishlist preview fetch failed.', ['wishlist_item_id' => $this->wishlistItemId, 'error' => $exception?->getMessage()]);
    }
}
