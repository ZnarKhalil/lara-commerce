<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->withCount('products')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse(
            CategoryResource::collection($categories)
        );
    }

    public function store(CategoryRequest $request)
    {
        $validated = $request->validated();
        
        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->successResponse(
            new CategoryResource($category),
            'Category created successfully',
            201
        );
    }

    public function show(Category $category)
    {
        $category->loadCount('products');
        
        return $this->successResponse($category);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return $this->successResponse($category, 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        // Check if category has products
        if ($category->products()->exists()) {
            return $this->errorResponse(
                'Cannot delete category with associated products. Remove products first.',
                409
            );
        }

        $category->delete();

        return $this->successResponse(null, 'Category deleted successfully');
    }

    public function products(Request $request, Category $category)
    {
        $products = $category->products()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->when($request->min_price, function ($query, $price) {
                $query->where('price', '>=', $price);
            })
            ->when($request->max_price, function ($query, $price) {
                $query->where('price', '<=', $price);
            })
            ->when($request->sort_by, function ($query) use ($request) {
                $sortField = in_array($request->sort_by, ['name', 'price', 'created_at']) 
                    ? $request->sort_by 
                    : 'created_at';
                
                $sortDirection = $request->sort_direction === 'asc' ? 'asc' : 'desc';
                $query->orderBy($sortField, $sortDirection);
            })
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($products);
    }
} 