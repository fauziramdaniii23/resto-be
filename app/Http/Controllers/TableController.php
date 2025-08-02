<?php

namespace App\Http\Controllers;

use App\Helper\ApiResponse;
use App\Models\Table;
use App\Repositories\TableRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    protected $tableRepository;
    public function __construct(TableRepository $tableRepository)
    {
        $this->tableRepository = $tableRepository;
    }
    public function getTables(Request $request) : JsonResponse
    {
        $params = $request->validate([
            'keyword' => 'nullable|string|numeric|max:255',
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1',
        ]);
        try {
            $result = $this->tableRepository->getDataTables($params['keyword'] ?? null, $params['page'], $params['pageSize']);
            $data = $result['data'];
            $total = $result['total'];

            $meta = [
                'data' => $data,
                'page' => $params['page'],
                'pageSize' => $params['pageSize'],
                'total' => $total,
            ];
            return ApiResponse::PaginateResponse($meta);
        } catch (\Exception $e) {
            return ApiResponse::ErrorResponse(
                null,
                $e->getMessage(),
            );
        }
    }
    public function generateTableNumber() : JsonResponse
    {
        try {
            $tableNumber = $this->tableRepository->generateTableNumber();
            return ApiResponse::BaseResponse(
                ['table_number' => $tableNumber],
                'Table number generated successfully',
            );
        }
        catch (\Exception $e) {
            return ApiResponse::ErrorResponse(
                null,
                $e->getMessage(),
            );
        }
    }

    public function upSertTable(Request $request) : JsonResponse
    {
        $data = $request->validate([
           'id' => 'nullable|integer|exists:tables,id',
            'table_number' => [
                'required',
                'integer',
                Rule::unique('tables', 'table_number')->ignore($request->id),
            ],
            'capacity' => 'required|integer|min:1',
        ]);
        try {
            DB::beginTransaction();
            $table = $this->tableRepository->upSertTable($data);
            DB::commit();
            return ApiResponse::BaseResponse(
                $table,
                'Table upserted successfully',
            );
        }
        catch (\Exception $e) {
            DB::rollBack();
            \Log::error("upSertTable error: " . $e->getMessage());
            return ApiResponse::ErrorResponse(
                'upSertTable error',
                $e->getMessage(),
            );
        }
    }

    public function deleteTable(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:tables,id']
        ]);
        try {
            DB::beginTransaction();
            $table = Table::findOrFail($request->id);
            $table->delete();
            DB::commit();

            return ApiResponse::BaseResponse($table, 'Table deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("DeleteTable error: " . $e->getMessage());
            return ApiResponse::ErrorResponse(
                "DeleteTable error: ",
                $e->getMessage()
            );
        }
    }
}
