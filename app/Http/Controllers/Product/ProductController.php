<?php

namespace App\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = max(1, min((int) $request->query('limit', 10), 100));
        $page = max(1, (int) $request->query('page', 1));
        $q = $request->query('q');
        return $this->productService->getProducts($limit, $page, $q);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        return $this->productService->createProduct($request->toDTO());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->productService->getProductById($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        return $this->productService->updateProduct($product, $request->toUpdateDTO());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->productService->deleteProduct($id);
    }
}
