<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductJsonSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductJsonSyncService $productJsonSyncService,
    ) {}

    public function index(): View
    {
        return view('products.index');
    }

    public function list(): JsonResponse
    {
        return response()->json($this->productJsonSyncService->snapshot());
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $snapshot = DB::transaction(function () use ($validated): array {
            Product::query()->create($validated);

            return $this->productJsonSyncService->sync();
        });

        return response()->json([
            ...$snapshot,
            'message' => 'Product added successfully.',
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        $snapshot = DB::transaction(function () use ($product, $validated): array {
            $product->update($validated);

            return $this->productJsonSyncService->sync();
        });

        return response()->json([
            ...$snapshot,
            'message' => 'Product updated successfully.',
        ]);
    }
}
