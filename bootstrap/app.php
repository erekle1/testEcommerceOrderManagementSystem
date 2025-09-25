<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
        ->withMiddleware(function (Middleware $middleware): void {
            $middleware->alias([
                'admin' => \App\Http\Middleware\AdminMiddleware::class,
                'customer' => \App\Http\Middleware\CustomerMiddleware::class,
                'stock.validation' => \App\Http\Middleware\StockValidationMiddleware::class,
                'json.response' => \App\Http\Middleware\EnsureJsonResponse::class,
            ]);
            
            // Apply JSON response middleware to all API routes
            $middleware->group('api', [
                'json.response',
            ]);
        })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API exceptions with standardized responses
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                        'version' => '1.0',
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                        'version' => '1.0',
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                        'version' => '1.0',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                        'version' => '1.0',
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed',
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                        'version' => '1.0',
                    ],
                ], 405);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                        'version' => '1.0',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'meta' => [
                        'timestamp' => now()->toISOString(),
                        'version' => '1.0',
                    ],
                ], 404);
            }
        });
    })->create();
