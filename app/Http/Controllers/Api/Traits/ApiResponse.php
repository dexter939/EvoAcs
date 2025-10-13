<?php

namespace App\Http\Controllers\Api\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ApiResponse Trait - Standardizza response API
 * ApiResponse Trait - Standardizes API responses
 * 
 * Fornisce metodi helper per response consistenti
 * Provides helper methods for consistent responses
 */
trait ApiResponse
{
    /**
     * Response con paginazione standard
     * Response with standard pagination
     * 
     * @param LengthAwarePaginator $paginator Laravel paginator
     * @return JsonResponse Response strutturata / Structured response
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Response con singolo record
     * Response with single record
     * 
     * @param mixed $data Dati da restituire / Data to return
     * @param int $status HTTP status code
     * @return JsonResponse Response strutturata / Structured response
     */
    protected function dataResponse($data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    /**
     * Response con messaggio di successo
     * Response with success message
     * 
     * @param string $message Messaggio / Message
     * @param mixed $data Dati opzionali / Optional data
     * @param int $status HTTP status code
     * @return JsonResponse Response strutturata / Structured response
     */
    protected function successResponse(string $message, $data = null, int $status = 200): JsonResponse
    {
        $response = ['message' => $message];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $status);
    }

    /**
     * Response con errore
     * Error response
     * 
     * @param string $message Messaggio errore / Error message
     * @param int $status HTTP status code
     * @return JsonResponse Response strutturata / Structured response
     */
    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $message
        ], $status);
    }

    /**
     * Response con successo e dati
     * Success response with data
     * 
     * @param mixed $data Dati da restituire / Data to return
     * @param int $status HTTP status code
     * @return JsonResponse Response con success=true e data / Response with success=true and data
     */
    protected function successDataResponse($data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data
        ], $status);
    }
}
