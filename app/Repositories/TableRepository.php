<?php

namespace App\Repositories;

use App\Models\Table;

class TableRepository
{
    public function getDataTables($keyword, $page, $pageSize): array
    {
        try {
            $offset = ($page - 1) * $pageSize;
            $query = Table::when($keyword, function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('table_number', 'ILIKE', "%$keyword%")
                            ->orWhere('capacity', 'ILIKE', "%$keyword%");
                    });
                });

            $total = $query->count();

            $data = $query->orderBy('table_number', 'asc')
                ->offset($offset)
                ->limit($pageSize)
                ->get();

            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }
        catch (\Exception $e) {
            \Log::error("GetDataTable error: " . $e->getMessage());
            throw new \Exception("GetDataTable error: " . $e->getMessage());
        }
    }
    public function generateTableNumber(): int
    {
        try {
            $maxTableNumber = Table::max('table_number');
            return $maxTableNumber ? $maxTableNumber + 1 : 1;
        } catch (\Exception $e) {
            \Log::error("GenerateTableNumber error: " . $e->getMessage());
            throw new \Exception("GenerateTableNumber error: " . $e->getMessage());
        }
    }

    public function upSertTable(array $data)
    {
        try {
            if (isset($data['id'])) {
                $table = Table::findOrFail($data['id']);

                $table->update([
                    'table_number' => $data['table_number'],
                    'capacity' => $data['capacity'],
                ]);

            } else {
                $table = Table::create([
                    'table_number' => $data['table_number'],
                    'capacity' => $data['capacity'],
                ]);
            }

            return $table;
        } catch (\Exception $e) {
            \Log::error("upSertTable error: " . $e->getMessage());
            throw new \Exception("upSertTable error: " . $e->getMessage());
        }
    }
}
