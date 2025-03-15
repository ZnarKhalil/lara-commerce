<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->when($request->category_id, function ($query, $categoryId) {
                $query->whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                });
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->with('categories')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($products);
    }

    public function store(ProductRequest $request)
    {
        $validated = $request->validated();

        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'sku' => $validated['sku'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $product->categories()->sync($validated['category_ids']);
        $product->load('categories');

        return $this->successResponse($product, 'Product created successfully', 201);
    }

    public function show(Product $product)
    {
        $product->load('categories');

        return $this->successResponse($product);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        if (isset($validated['category_ids'])) {
            $product->categories()->sync($validated['category_ids']);
        }

        $product->load('categories');

        return $this->successResponse($product, 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return $this->successResponse(null, 'Product deleted successfully');
    }

    public function search($query)
    {
        $products = Product::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->with('categories')
            ->paginate(15);

        return $this->successResponse($products);
    }

    public function related(Product $product)
    {
        $related = Product::whereHas('categories', function ($query) use ($product) {
            $query->whereIn('categories.id', $product->categories->pluck('id'));
        })
            ->where('id', '!=', $product->id)
            ->limit(8)
            ->get();

        return $this->successResponse($related);
    }
}
