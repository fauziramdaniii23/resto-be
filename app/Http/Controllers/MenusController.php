<?php

namespace App\Http\Controllers;

use App\Helper\ApiResponse;
use App\Repositories\MenusRepository;
use Cloudinary\Cloudinary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenusController extends Controller
{
    protected $menusRepository;

    public function __construct(MenusRepository $menusRepository)
    {
        $this->menusRepository = $menusRepository;
    }

    public function getMenus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'keyword' => 'nullable|string|max:255',
                'category_id' => 'nullable|integer|exists:categories,id',
                'page' => 'required|integer|min:1',
                'pageSize' => 'required|integer|min:1',
            ]);
            $result = $this->menusRepository->getMenus(
                $validated['keyword'] ?? null,
                $validated['category_id'] ?? null,
                $validated['page'],
                $validated['pageSize']
            );
            $data = $result['data'];
            $total = $result['total'];
            $meta_data = [
                'data' => $data,
                'page' => $result['page'],
                'pageSize' => $result['pageSize'],
                'total' => $total,
            ];
            return ApiResponse::PaginateResponse($meta_data);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }

    public function getMenusById(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:menus,id',
            ]);
            $menu = $this->menusRepository->getMenusById($validated['id']);
            return ApiResponse::BaseResponse($menu, 'Menu retrieved successfully');
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }

    public function upSertMenus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'nullable|integer|exists:menus,id',
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|integer|exists:categories,id',
                'description' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
            DB::beginTransaction();
            $menu = $this->menusRepository->upSertMenus($validated);
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $cloudinary = new Cloudinary();
                $uploadResult = $cloudinary->uploadApi()->upload(
                    $uploadedFile->getRealPath(),
                    ['folder' => 'laravel/resto/menus']
                );
                $uploadedFileUrl = $uploadResult['secure_url'] ?? null;
                $cloudImageId = $uploadResult['public_id'] ?? null;
                $menu->images()->create([
                    'image_url' => $uploadedFileUrl,
                    'cloud_id' => $cloudImageId,
                ]);
            }

            DB::commit();
            return ApiResponse::BaseResponse($menu->load('images'), 'Menu saved successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }
}
