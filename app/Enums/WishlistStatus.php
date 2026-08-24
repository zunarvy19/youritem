<?php

namespace App\Enums;

enum WishlistStatus: string
{
    case Active = 'ACTIVE';
    case Purchased = 'PURCHASED';
    case Archived = 'ARCHIVED';
}
