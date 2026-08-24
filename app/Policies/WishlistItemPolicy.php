<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Auth\Access\Response;

class WishlistItemPolicy
{
    public function view(User $user, WishlistItem $wishlistItem): Response
    {
        return $user->id === $wishlistItem->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, WishlistItem $wishlistItem): Response
    {
        return $user->id === $wishlistItem->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function archive(User $user, WishlistItem $wishlistItem): Response
    {
        return $user->id === $wishlistItem->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function purchase(User $user, WishlistItem $wishlistItem): Response
    {
        return $user->id === $wishlistItem->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
