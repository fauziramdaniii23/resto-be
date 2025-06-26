<?php

namespace App\Http\Controllers;

use App\Helper\ApiResponse;
use App\Models\Menus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenusController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $menus = Menus::all();
            return ApiResponse::BaseResponse($menus);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }
}
