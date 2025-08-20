<?php

namespace App\Repositories;

use App\Models\Menus;

class MenusRepository
{
    public function getMenus($keyword, $category_id, $page, $pageSize)
    {
        try {
            $offset = ($page - 1) * $pageSize;
            $query = Menus::with('categories', 'images');
            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'ILIKE', '%' . $keyword . '%')
                        ->orWhere('description', 'ILIKE', '%' . $keyword . '%')
                        ->orWhere('price', 'ILIKE', '%' . $keyword . '%')
                        ->orWhereHas('categories', function ($q) use ($keyword) {
                            $q->where('name', 'ILIKE', '%' . $keyword . '%');
                        });
                });
            }

            if ($category_id) {
                $query->where('category_id', $category_id);
            }
            $total = $query->count();

            $data = $query->orderBy('updated_at', 'desc')
                ->offset($offset)
                ->limit($pageSize)
                ->get();

            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        } catch (\Exception $e) {
            \Log::error("GetDataMenus error: " . $e->getMessage());
            throw new \Exception("GetDataMenus error: " . $e->getMessage());
        }
    }
    public function upSertMenus(array $data)
    {
        try {
            if(isset($data['id'])) {
                $menu = Menus::findOrFail($data['id']);
                $menu->update([
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'category_id' => $data['category_id'],
                    'description' => $data['description'],
                    'image' => $data['image'] ?? null,
                ]);
            } else {
                $menu = Menus::create($data);
            }
            return $menu;
        }
        catch (\Exception $e) {
            \Log::error("UpSertMenus error: " . $e->getMessage());
            throw new \Exception("UpSertMenus error: " . $e->getMessage());
        }
    }
}
