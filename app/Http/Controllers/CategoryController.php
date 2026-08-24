<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $includeInactive = $request->boolean('include_inactive');

        $categories = Category::query()
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated())->refresh();

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category->refresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->wishlistItems()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Categories used by wishlist items cannot be deleted. Deactivate this category instead.',
            ]);
        }

        $category->delete();

        return response()->json(status: 204);
    }
}
