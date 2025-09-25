<?php

namespace App\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class BaseApiController extends Controller
{
    /**
     * Return a successful JSON response with data.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operation completed successfully',
        int $statusCode = Response::HTTP_OK,
        array $additional = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
                ...$additional,
            ],
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return a successful response with a single resource.
     */
    protected function resourceResponse(
        JsonResource $resource,
        string $message = 'Resource retrieved successfully',
        int $statusCode = Response::HTTP_OK,
        array $additional = []
    ): JsonResponse {
        return $this->successResponse(
            $resource,
            $message,
            $statusCode,
            $additional
        );
    }

    /**
     * Return a successful response with a paginated resource collection.
     */
    protected function paginatedResponse(
        AnonymousResourceCollection $collection,
        string $message = 'Resources retrieved successfully',
        int $statusCode = Response::HTTP_OK,
        array $additional = []
    ): JsonResponse {
        $paginationData = $collection->response()->getData(true);
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $paginationData['data'],
            'pagination' => [
                'current_page' => $paginationData['meta']['current_page'],
                'per_page' => $paginationData['meta']['per_page'],
                'total' => $paginationData['meta']['total'],
                'last_page' => $paginationData['meta']['last_page'],
                'from' => $paginationData['meta']['from'],
                'to' => $paginationData['meta']['to'],
                'has_more_pages' => $paginationData['meta']['current_page'] < $paginationData['meta']['last_page'],
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
                ...$additional,
            ],
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return a successful response with a created resource.
     */
    protected function createdResponse(
        JsonResource $resource,
        string $message = 'Resource created successfully',
        array $additional = []
    ): JsonResponse {
        return $this->resourceResponse(
            $resource,
            $message,
            Response::HTTP_CREATED,
            $additional
        );
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(
        string $message = 'An error occurred',
        array $errors = [],
        int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY,
        array $additional = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
                ...$additional,
            ],
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->errorResponse($message, $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, [], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, [], Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, [], Response::HTTP_NOT_FOUND);
    }

    /**
     * Return a method not allowed response.
     */
    protected function methodNotAllowedResponse(string $message = 'Method not allowed'): JsonResponse
    {
        return $this->errorResponse($message, [], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Handle model not found exceptions gracefully.
     */
    protected function handleModelNotFound(ModelNotFoundException $e, string $resourceName = 'Resource'): JsonResponse
    {
        return $this->notFoundResponse("{$resourceName} not found");
    }

    /**
     * Handle validation exceptions gracefully.
     */
    protected function handleValidationException(ValidationException $e): JsonResponse
    {
        return $this->validationErrorResponse($e->errors(), $e->getMessage());
    }

    /**
     * Handle general exceptions gracefully.
     */
    protected function handleException(\Exception $e, string $message = 'An unexpected error occurred'): JsonResponse
    {
        // Log the exception for debugging
        logger()->error('API Exception: ' . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->errorResponse($message, [], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Execute a callback and handle any exceptions that occur.
     */
    protected function handleExceptions(callable $callback, string $errorMessage = 'An error occurred'): JsonResponse
    {
        try {
            return $callback();
        } catch (ModelNotFoundException $e) {
            return $this->handleModelNotFound($e);
        } catch (ValidationException $e) {
            return $this->handleValidationException($e);
        } catch (\Exception $e) {
            return $this->handleException($e, $errorMessage);
        }
    }

    /**
     * Find a model or return a not found response.
     */
    protected function findOrFailResponse(string $modelClass, mixed $id, string $resourceName = 'Resource'): JsonResponse
    {
        try {
            $model = $modelClass::findOrFail($id);
            return $this->successResponse($model);
        } catch (ModelNotFoundException $e) {
            return $this->handleModelNotFound($e, $resourceName);
        }
    }
}
