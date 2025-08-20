<?php

namespace App\Http\Controllers;

use App\Helper\ApiResponse;
use App\Models\Categories;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function getCategories()
    {
        try {
            $categories = Categories::all();
            return ApiResponse::BaseResponse($categories, 'Categories retrieved successfully');
        }catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }
}
