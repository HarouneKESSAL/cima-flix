<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Generate a success response.
     *
     * @param  null  $data
     * @param  null  $extra
     */
    public static function success(string $message, $data = null, ?int $page = null, ?int $size = null, $total = null, $extra = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => $message,
        ];

        if ($data != null) {
            $response['data'] = $data;
        }

        if ($total != null || $page != null && $size != null) {
            $response['total'] = $total ?? 0;
        }

        if ($page != null && $size != null) {
            $response['page'] = $page;
            $response['size'] = $size;
        }

        if ($extra != null) {
            $response['extra'] = $extra;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Generate an error response.
     */
    public static function error(string $message, string $code, int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'status' => false,
            'message' => $message,
            'code' => $code,
        ];
        if ($errors != null) {
            $response['errors'] = $errors;
        }
        return response()->json($response, $statusCode);
    }
}
