<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Product;
use Illuminate\Http\Exceptions\HttpResponseException;

class StockValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a cart or order request
        if ($request->isMethod('POST') && ($request->routeIs('cart.store') || $request->routeIs('orders.store'))) {
            $this->validateStock($request);
        }

        return $next($request);
    }

    /**
     * Validate stock availability.
     */
    private function validateStock(Request $request): void
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity', 1);

        if ($productId) {
            $product = Product::find($productId);
            
            if (!$product) {
                throw new HttpResponseException(
                    response()->json(['message' => 'Product not found'], 404)
                );
            }

            if (!$product->isInStock($quantity)) {
                throw new HttpResponseException(
                    response()->json([
                        'message' => 'Insufficient stock',
                        'available_stock' => $product->stock,
                        'requested_quantity' => $quantity,
                    ], 422)
                );
            }
        }
    }
}
