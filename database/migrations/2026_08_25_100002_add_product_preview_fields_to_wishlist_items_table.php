<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wishlist_items', function (Blueprint $table): void {
            $table->text('product_url')->nullable();
            $table->string('preview_title')->nullable();
            $table->text('preview_description')->nullable();
            $table->text('preview_image_url')->nullable();
            $table->string('preview_site_name')->nullable();
            $table->timestamp('preview_fetched_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wishlist_items', fn (Blueprint $table) => $table->dropColumn([
            'product_url', 'preview_title', 'preview_description', 'preview_image_url', 'preview_site_name', 'preview_fetched_at',
        ]));
    }
};
