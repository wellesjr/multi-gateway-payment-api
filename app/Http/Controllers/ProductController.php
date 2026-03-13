<?php

namespace App\Http\Controllers;

use App\Dtos\Product\CreateProductDto;
use App\Dtos\Product\UpdateProductDto;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\ApiResponse;
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

        return ApiResponse::success(
            message: 'Produtos listados com sucesso.',
            data: ProductResource::collection($products),
            meta: [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $dto     = CreateProductDto::fromRequest($request);
        $product = $this->productService->create($dto);

        return ApiResponse::success(
            message: 'Produto criado com sucesso.',
            data: new ProductResource($product),
            status: 201,
        );
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(
            message: 'Produto encontrado com sucesso.',
            data: new ProductResource($product),
        );
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse|Response
    {
        $dto = UpdateProductDto::fromRequest($request);

        if (empty($dto->toArray())) {
            return response()->noContent();
        }

        $updatedProduct = $this->productService->update($product, $dto);

        return ApiResponse::success(
            message: 'Produto atualizado com sucesso.',
            data: new ProductResource($updatedProduct),
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return ApiResponse::success('Produto excluído com sucesso.');
    }
}
