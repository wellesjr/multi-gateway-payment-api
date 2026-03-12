<?php

namespace App\Http\Controllers;

use App\Dtos\Product\CreateProductDto;
use App\Dtos\Product\UpdateProductDto;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(): JsonResponse
    {
        $products = $this->productService->list();

        return response()->json([
            'success' => true,
            'data'    => ProductResource::collection($products),
            'meta'    => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $dto     = CreateProductDto::fromRequest($request);
        $product = $this->productService->create($dto);

        return response()->json([
            'success' => true,
            'message' => 'Produto criado com sucesso.',
            'data'    => new ProductResource($product),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new ProductResource($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse|Response
    {
        $dto = UpdateProductDto::fromRequest($request);

        if (empty($dto->toArray())) {
            return response()->noContent();
        }

        $updatedProduct = $this->productService->update($product, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Produto atualizado com sucesso.',
            'data'    => new ProductResource($updatedProduct),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json([
            'success' => true,
            'message' => 'Produto excluído com sucesso.',
        ]);
    }
}
