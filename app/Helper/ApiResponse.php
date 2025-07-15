<?php

namespace App\Helper;

use Illuminate\Http\JsonResponse;
use phpseclib3\Math\BigInteger;

class ApiResponse
{
    public static function BaseResponse(mixed $data, string $info = '', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => $statusCode,
            'data' => $data,
            'info' => $info
        ], $statusCode);
    }
    public static function ErrorResponse(mixed $data, string $info = '', int $statusCode = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'status' => $statusCode,
            'data' => $data,
            'info' => $info
        ], $statusCode);
    }
    public static function PaginateResponse(mixed $data, int $total ,string $info = '', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => $statusCode,
            'data' => $data,
            'info' => $info,
            'total' => $total
        ], $statusCode);
    }
}
